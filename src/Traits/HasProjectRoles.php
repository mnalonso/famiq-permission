<?php

declare(strict_types=1);

namespace Famiq\Permission\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Famiq\Permission\Models\Role;
use Famiq\Permission\Models\UserRole;
use Famiq\Permission\Services\PermissionService;

/**
 * Trait that exposes helper methods to the User model.
 */
trait HasProjectRoles
{
    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class, 'user_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            famiq_permission_table_name('user_role'),
            'user_id',
            'role_id'
        )->withPivot('project_id');
    }

    public function rolesGlobal(): BelongsToMany
    {
        return $this->roles()->wherePivotNull('project_id');
    }

    public function rolesInProject($project): BelongsToMany
    {
        $projectId = $this->resolveProjectId($project);

        return $this->roles()->wherePivot('project_id', $projectId);
    }

    public function hasRoleInProject(string $roleSlug, $project = null): bool
    {
        return $this->projectRolesService()->userHasRoleInProject($this, $roleSlug, $project);
    }

    public function hasRoleGlobal(string $roleSlug): bool
    {
        return $this->projectRolesService()->userHasRoleGlobal($this, $roleSlug);
    }

    public function canInProject(string $permissionSlug, $project): bool
    {
        return $this->projectRolesService()->userHasPermissionInProject($this, $permissionSlug, $project);
    }

    public function canGlobal(string $permissionSlug): bool
    {
        return $this->projectRolesService()->userHasPermissionGlobal($this, $permissionSlug);
    }

    protected function projectRolesService(): PermissionService
    {
        return app(PermissionService::class);
    }

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
}
