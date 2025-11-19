<?php

declare(strict_types=1);

namespace Famiq\Permission\Traits;

use Famiq\Permission\Models\Role;
use Famiq\Permission\Models\UserRole;
use Famiq\Permission\Services\PermissionService;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Variante en español del trait HasProjectRoles para evitar colisiones de nombres.
 */
trait TieneRolesDeProyecto
{
    /**
     * Relación directa a los registros pivot user_role del paquete.
     */
    public function rolesDeUsuario(): HasMany
    {
        return $this->hasMany(UserRole::class, 'user_id');
    }

    /**
     * Roles asociados al usuario en cualquier contexto.
     */
    public function rolesAsignados(): BelongsToMany
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
    public function rolesGlobales(): BelongsToMany
    {
        return $this->rolesAsignados()->wherePivotNull('project_id');
    }

    /**
     * Roles del usuario habilitados para un proyecto específico.
     *
     * @param  int|object  $project
     */
    public function rolesEnProyecto($project): BelongsToMany
    {
        $projectId = $this->resolverIdDeProyecto($project);

        return $this->rolesAsignados()->wherePivot('project_id', $projectId);
    }

    /**
     * Indica si el usuario tiene un rol dentro de un proyecto (o considerando también los globales cuando corresponda).
     */
    public function tieneRolEnProyecto(string $roleSlug, $project = null): bool
    {
        return $this->servicioRolesDeProyecto()->userHasRoleInProject($this, $roleSlug, $project);
    }

    /**
     * Indica si el usuario posee un rol global.
     */
    public function tieneRolGlobal(string $roleSlug): bool
    {
        return $this->servicioRolesDeProyecto()->userHasRoleGlobal($this, $roleSlug);
    }

    /**
     * Comprueba permisos considerando un proyecto en particular.
     *
     * @param  int|object  $project
     */
    public function puedeEnProyecto(string $permissionSlug, $project): bool
    {
        return $this->servicioRolesDeProyecto()->userHasPermissionInProject($this, $permissionSlug, $project);
    }

    /**
     * Comprueba permisos globales (sin proyecto asociado).
     */
    public function puedeGlobal(string $permissionSlug): bool
    {
        return $this->servicioRolesDeProyecto()->userHasPermissionGlobal($this, $permissionSlug);
    }

    /**
     * Determina si el usuario tiene el permiso en cualquier proyecto habilitado o a nivel global.
     */
    public function puedeEnCualquierContexto(string $permissionSlug): bool
    {
        return $this->servicioRolesDeProyecto()->userHasPermission($this, $permissionSlug);
    }

    /**
     * Obtiene la instancia del servicio central desde el contenedor.
     */
    protected function servicioRolesDeProyecto(): PermissionService
    {
        return app(PermissionService::class);
    }

    /**
     * Convierte la referencia del proyecto en su identificador entero.
     *
     * @param  int|object  $project
     */
    protected function resolverIdDeProyecto($project): int
    {
        if (is_numeric($project)) {
            return (int) $project;
        }

        if (is_object($project) && method_exists($project, 'getKey')) {
            return (int) $project->getKey();
        }

        throw new \InvalidArgumentException('La referencia del proyecto no es válida.');
    }
}
