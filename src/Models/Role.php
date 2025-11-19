<?php

declare(strict_types=1);

namespace Famiq\Permission\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

/**
 * Role entity that can be assigned globally or per project.
 */
class Role extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'scope',
        'order',
    ];

    /**
     * Devuelve el nombre de la tabla usando el prefijo configurado.
     */
    public function getTable(): string
    {
        return famiq_permission_table_name('roles');
    }

    /**
     * Permisos asociados a este rol.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            famiq_permission_table_name('role_permission'),
            'role_id',
            'permission_id'
        );
    }

    /**
     * Proyectos en donde el rol está habilitado.
     */
    public function projects(): BelongsToMany
    {
        $projectModel = config('famiq-permission.project_model');

        return $this->belongsToMany(
            $projectModel,
            famiq_permission_table_name('project_role'),
            'role_id',
            'project_id'
        );
    }

    /**
     * Relación hacia los pivots user_role que usan este rol.
     */
    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class, 'role_id');
    }

    /**
     * Asocia uno o varios permisos al rol sin sobrescribir los existentes.
     *
     * @param  Permission|int|string|array<int, Permission|int|string>  ...$permissions
     */
    public function givePermissionTo($permissions): self
    {
        $permissions = func_num_args() === 1 && is_array($permissions)
            ? $permissions
            : func_get_args();

        if ($permissions === []) {
            throw new InvalidArgumentException('Debe proporcionar al menos un permiso.');
        }

        $permissionIds = array_map(function ($permission): int {
            return $this->resolvePermissionId($permission);
        }, $permissions);

        $this->permissions()->syncWithoutDetaching(array_unique($permissionIds));

        return $this;
    }

    /**
     * Revoca uno o varios permisos del rol.
     *
     * @param  Permission|int|string|array<int, Permission|int|string>  ...$permissions
     */
    public function revokePermissionTo($permissions): self
    {
        $permissions = func_num_args() === 1 && is_array($permissions)
            ? $permissions
            : func_get_args();

        if ($permissions === []) {
            throw new InvalidArgumentException('Debe proporcionar al menos un permiso.');
        }

        $permissionIds = array_map(function ($permission): int {
            return $this->resolvePermissionId($permission);
        }, $permissions);

        $this->permissions()->detach(array_unique($permissionIds));

        return $this;
    }

    /**
     * Normaliza el identificador del permiso recibido.
     *
     * @param  Permission|int|string  $permission
     */
    protected function resolvePermissionId($permission): int
    {
        if ($permission instanceof Permission) {
            return (int) $permission->getKey();
        }

        if (is_numeric($permission)) {
            return (int) $permission;
        }

        if (is_string($permission)) {
            $permissionModel = Permission::query()->where('slug', $permission)->first();

            if ($permissionModel === null) {
                throw (new ModelNotFoundException())->setModel(Permission::class, [$permission]);
            }

            return (int) $permissionModel->getKey();
        }

        throw new InvalidArgumentException('Referencia de permiso inválida.');
    }
}
