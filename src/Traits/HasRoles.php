<?php

declare(strict_types=1);

namespace Famiq\Permission\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Famiq\Permission\Models\Permission;
use Famiq\Permission\Models\Role;
use Famiq\Permission\Models\UserRole;
use Famiq\Permission\Services\PermissionService;

/**
 * Trait that exposes helper methods to the User model, mirroring Spatie's HasRoles API while keeping project-aware logic.
 */
trait HasRoles
{
    /**
     * Direct relation to pivot records defined by the package.
     */
    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class, 'user_id');
    }

    /**
     * Roles assigned to the user regardless of context.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            famiq_permission_table_name('user_role'),
            'user_id',
            'role_id'
        )->withPivot('project_id');
    }

    /**
     * User roles granted globally (without project scope).
     */
    public function rolesGlobal(): BelongsToMany
    {
        return $this->roles()->wherePivotNull('project_id');
    }

    /**
     * User roles enabled for a specific project.
     *
     * @param  int|object  $project
     */
    public function rolesInProject($project): BelongsToMany
    {
        $projectId = $this->resolveProjectId($project);

        return $this->roles()->wherePivot('project_id', $projectId);
    }

    /**
     * Determines if the user has a given role globally or for the provided project.
     */
    public function hasRoleInProject(string $roleSlug, $project = null): bool
    {
        return $this->projectRolesService()->userHasRoleInProject($this, $roleSlug, $project);
    }

    /**
     * Determines if the user has a global role.
     */
    public function hasRoleGlobal(string $roleSlug): bool
    {
        return $this->projectRolesService()->userHasRoleGlobal($this, $roleSlug);
    }

    /**
     * Checks permissions for a specific project.
     *
     * @param  int|object  $project
     */
    public function canInProject(string $permissionSlug, $project): bool
    {
        return $this->projectRolesService()->userHasPermissionInProject($this, $permissionSlug, $project);
    }

    /**
     * Checks global permissions (without project context).
     */
    public function canGlobal(string $permissionSlug): bool
    {
        return $this->projectRolesService()->userHasPermissionGlobal($this, $permissionSlug);
    }

    /**
     * Determines if the user has the permission anywhere (globally or in any project).
     */
    public function canAnywhere(string $permissionSlug): bool
    {
        return $this->projectRolesService()->userHasPermission($this, $permissionSlug);
    }

    /**
     * Provides compatibility with Spatie's API.
     *
     * @param  int|object|null  $project
     */
    public function hasPermissionTo($permission, $project = null): bool
    {
        $permissionSlug = $this->resolvePermissionSlug($permission);

        return $project === null
            ? $this->canAnywhere($permissionSlug)
            : $this->canInProject($permissionSlug, $project);
    }

    /**
     * Returns all permissions reachable through the assigned roles.
     */
    public function permissions()
    {
        return $this->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->unique('id')
            ->values();
    }

    /**
     * Retrieves every permission slug available to the user globally or in any project.
     *
     * @return Collection<int, string>
     */
    public function getAllPermissions(): Collection
    {
        $permissionTable = famiq_permission_table_name('permissions');
        $rolePermissionTable = famiq_permission_table_name('role_permission');
        $userRoleTable = famiq_permission_table_name('user_role');

        return Permission::query()
            ->select($permissionTable.'.slug')
            ->join($rolePermissionTable, $rolePermissionTable.'.permission_id', '=', $permissionTable.'.id')
            ->join($userRoleTable, $userRoleTable.'.role_id', '=', $rolePermissionTable.'.role_id')
            ->where($userRoleTable.'.user_id', $this->getKey())
            ->where(function (Builder $builder) use ($permissionTable, $userRoleTable): void {
                $builder
                    ->where(function (Builder $query) use ($permissionTable, $userRoleTable): void {
                        $query->whereNull($permissionTable.'.project_id')
                            ->whereNull($userRoleTable.'.project_id');
                    })
                    ->orWhere(function (Builder $query) use ($permissionTable, $userRoleTable): void {
                        $query->whereNotNull($permissionTable.'.project_id')
                            ->where(function (Builder $inner) use ($permissionTable, $userRoleTable): void {
                                $inner->whereNull($userRoleTable.'.project_id')
                                    ->orWhereColumn($permissionTable.'.project_id', $userRoleTable.'.project_id');
                            });
                    });
            })
            ->distinct()
            ->pluck('slug');
    }

    /**
     * Determines if the user has any of the provided permissions.
     *
     * @param  string|array<int, string>  $permissions
     */
    public function hasAnyPermission($permissions, $project = null): bool
    {
        $permissionList = is_array($permissions) ? $permissions : [$permissions];

        foreach ($permissionList as $permission) {
            if ($this->hasPermissionTo($permission, $project)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determines if the user has all the provided permissions.
     *
     * @param  string|array<int, string>  $permissions
     */
    public function hasAllPermissions($permissions, $project = null): bool
    {
        $permissionList = is_array($permissions) ? $permissions : [$permissions];

        foreach ($permissionList as $permission) {
            if (! $this->hasPermissionTo($permission, $project)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Overrides Laravel's can helper to leverage project-aware permissions.
     */
    public function can($ability, $arguments = []): bool
    {
        $project = is_array($arguments) && count($arguments) > 0
            ? $arguments[0]
            : null;

        if ($this->hasPermissionTo($ability, $project)) {
            return true;
        }

        $parentClass = get_parent_class($this) ?: '';

        return method_exists($parentClass, 'can')
            ? parent::can($ability, $arguments)
            : false;
    }

    /**
     * Returns the role names assigned to the user.
     */
    public function getRoleNames()
    {
        return $this->roles()->pluck('name');
    }

    /**
     * Assigns one or multiple roles to the user for a given context.
     *
     * @param  Role|int|string|array<int, Role|int|string>  $roles
     */
    public function assignRole($roles, $project = null): self
    {
        $roleList = is_array($roles) ? $roles : [$roles];

        $projectId = $project === null ? null : $this->resolveProjectId($project);

        $payload = [];

        foreach ($roleList as $role) {
            $roleId = $this->resolveRoleId($role);
            $payload[$roleId] = ['project_id' => $projectId];
        }

        $this->roles()->syncWithoutDetaching($payload);

        return $this;
    }

    /**
     * Removes one or multiple roles from the user for a given context.
     *
     * @param  Role|int|string|array<int, Role|int|string>  $roles
     */
    public function removeRole($roles, $project = null): self
    {
        $roleList = is_array($roles) ? $roles : [$roles];

        $roleIds = array_map(function ($role): int {
            return $this->resolveRoleId($role);
        }, $roleList);

        $query = $this->userRoles()->whereIn('role_id', $roleIds);

        if ($project === null) {
            $query->whereNull('project_id');
        } else {
            $query->where('project_id', $this->resolveProjectId($project));
        }

        $query->delete();

        return $this;
    }

    /**
     * Synchronizes the user roles for the given context.
     *
     * @param  Role|int|string|array<int, Role|int|string>  $roles
     */
    public function syncRoles($roles, $project = null): self
    {
        $projectId = $project === null ? null : $this->resolveProjectId($project);

        $this->userRoles()
            ->when($projectId === null, function ($query): void {
                $query->whereNull('project_id');
            }, function ($query) use ($projectId): void {
                $query->where('project_id', $projectId);
            })
            ->delete();

        return $this->assignRole($roles, $project);
    }

    /**
     * Fetches the central service instance from the container.
     */
    protected function projectRolesService(): PermissionService
    {
        return app(PermissionService::class);
    }

    /**
     * Normalizes the project reference into its integer identifier.
     *
     * @param  int|object  $project
     */
    protected function resolveProjectId($project): int
    {
        if (is_numeric($project)) {
            return (int) $project;
        }

        if (is_object($project) && method_exists($project, 'getKey')) {
            return (int) $project->getKey();
        }

        throw new \InvalidArgumentException('Project reference is invalid.');
    }

    /**
     * Normalizes the role reference into its identifier.
     *
     * @param  Role|int|string  $role
     */
    protected function resolveRoleId($role): int
    {
        if ($role instanceof Role) {
            return (int) $role->getKey();
        }

        if (is_numeric($role)) {
            return (int) $role;
        }

        if (is_string($role)) {
            $roleModel = Role::query()->where('slug', $role)->first();

            if ($roleModel === null) {
                throw new \InvalidArgumentException('Role reference is invalid.');
            }

            return (int) $roleModel->getKey();
        }

        throw new \InvalidArgumentException('Role reference is invalid.');
    }

    /**
     * Normalizes the permission reference into its slug.
     *
     * @param  string|object  $permission
     */
    protected function resolvePermissionSlug($permission): string
    {
        if (is_string($permission)) {
            return $permission;
        }

        if (is_object($permission) && isset($permission->slug)) {
            return (string) $permission->slug;
        }

        throw new \InvalidArgumentException('Permission reference is invalid.');
    }
}
