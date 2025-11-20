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
    /**
     * Relación directa a los registros pivot user_role del paquete.
     */
    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class, 'user_id');
    }

    /**
     * Roles asociados al usuario en cualquier contexto.
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
     * Roles del usuario asignados de forma global (sin proyecto).
     */
    public function rolesGlobal(): BelongsToMany
    {
        return $this->roles()->wherePivotNull('project_id');
    }

    /**
     * Roles del usuario habilitados para un proyecto específico.
     *
     * @param  int|object  $project
     */
    public function rolesInProject($project): BelongsToMany
    {
        $projectId = $this->resolveProjectId($project);

        return $this->roles()->wherePivot('project_id', $projectId);
    }

    /**
     * Indica si el usuario tiene un rol dentro de un proyecto (o considerando también los globales cuando corresponda).
     */
    public function hasRoleInProject(string $roleSlug, $project = null): bool
    {
        return $this->projectRolesService()->userHasRoleInProject($this, $roleSlug, $project);
    }

    /**
     * Indica si el usuario posee un rol global.
     */
    public function hasRoleGlobal(string $roleSlug): bool
    {
        return $this->projectRolesService()->userHasRoleGlobal($this, $roleSlug);
    }

    /**
     * Comprueba permisos considerando un proyecto en particular.
     *
     * @param  int|object  $project
     */
    public function canInProject(string $permissionSlug, $project): bool
    {
        return $this->projectRolesService()->userHasPermissionInProject($this, $permissionSlug, $project);
    }

    /**
     * Comprueba permisos globales (sin proyecto asociado).
     */
    public function canGlobal(string $permissionSlug): bool
    {
        return $this->projectRolesService()->userHasPermissionGlobal($this, $permissionSlug);
    }

    /**
     * Determina si el usuario tiene el permiso en cualquier proyecto habilitado o a nivel global.
     */
    public function canAnywhere(string $permissionSlug): bool
    {
        return $this->projectRolesService()->userHasPermission($this, $permissionSlug);
    }

    /*
     * Se agrega método para compatibilidad con interfaz de Spatie
     *
     * @param  int|object|null  $project
    */
    public function hasPermission(string $permissionSlug, $project = null): bool
    {
        if ($project) {
            return $this->canInProject($permissionSlug, $project);
        } else {
            return $this->canAnywhere($permissionSlug);
        }
    }

    /**
     * Obtiene la instancia del servicio central desde el contenedor.
     */
    protected function projectRolesService(): PermissionService
    {
        return app(PermissionService::class);
    }

    /**
     * Convierte la referencia del proyecto en su identificador entero.
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
}
