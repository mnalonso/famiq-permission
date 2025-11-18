<?php

declare(strict_types=1);

namespace Famiq\Permission\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Famiq\Permission\Models\Permission;
use Famiq\Permission\Models\UserRole;

/**
 * Central service with the authorization helpers consumed by the trait and facade.
 */
class PermissionService
{
    /**
     * Determina si el usuario posee un rol global o específico de un proyecto.
     *
     * @param  Model       $user      Instancia del usuario autenticado.
     * @param  string      $roleSlug  Slug del rol a validar.
     * @param  int|Model|null $project Proyecto o identificador cuando corresponde.
     */
    public function userHasRoleInProject(Model $user, string $roleSlug, $project = null): bool
    {
        $projectId = $project === null ? null : $this->resolveProjectId($project);

        $query = $this->baseRoleQuery($user, $roleSlug);

        if ($projectId === null) {
            $query->whereNull(famiq_permission_table_name('user_role').'.project_id');
        } else {
            $query->where(function (Builder $builder) use ($projectId): void {
                $table = famiq_permission_table_name('user_role');
                $builder->whereNull($table.'.project_id')->orWhere($table.'.project_id', $projectId);
            });
        }

        return $query->exists();
    }

    /**
     * Indica si el usuario posee un rol global.
     */
    public function userHasRoleGlobal(Model $user, string $roleSlug): bool
    {
        return $this->userHasRoleInProject($user, $roleSlug, null);
    }

    /**
     * Valida si el usuario cuenta con un permiso considerando el contexto de un proyecto.
     *
     * @param  Model       $user             Usuario evaluado.
     * @param  string      $permissionSlug   Slug del permiso buscado.
     * @param  int|Model   $project          Proyecto o identificador relacionado.
     */
    public function userHasPermissionInProject(Model $user, string $permissionSlug, $project): bool
    {
        $projectId = $this->resolveProjectId($project);

        $permissionTable = famiq_permission_table_name('permissions');
        $rolePermissionTable = famiq_permission_table_name('role_permission');
        $userRoleTable = famiq_permission_table_name('user_role');

        return Permission::query()
            ->select($permissionTable.'.id')
            ->join($rolePermissionTable, $rolePermissionTable.'.permission_id', '=', $permissionTable.'.id')
            ->join($userRoleTable, $userRoleTable.'.role_id', '=', $rolePermissionTable.'.role_id')
            ->where($userRoleTable.'.user_id', $user->getKey())
            ->where(function (Builder $builder) use ($userRoleTable, $projectId): void {
                $builder->whereNull($userRoleTable.'.project_id')
                    ->orWhere($userRoleTable.'.project_id', $projectId);
            })
            ->where(function (Builder $builder) use ($permissionTable, $projectId): void {
                $builder->whereNull($permissionTable.'.project_id')
                    ->orWhere($permissionTable.'.project_id', $projectId);
            })
            ->where($permissionTable.'.slug', $permissionSlug)
            ->exists();
    }

    /**
     * Comprueba si el usuario tiene un permiso ya sea globalmente o en cualquier proyecto disponible.
     */
    public function userHasPermission(Model $user, string $permissionSlug): bool
    {
        $permissionTable = famiq_permission_table_name('permissions');
        $rolePermissionTable = famiq_permission_table_name('role_permission');
        $userRoleTable = famiq_permission_table_name('user_role');

        return Permission::query()
            ->select($permissionTable.'.id')
            ->join($rolePermissionTable, $rolePermissionTable.'.permission_id', '=', $permissionTable.'.id')
            ->join($userRoleTable, $userRoleTable.'.role_id', '=', $rolePermissionTable.'.role_id')
            ->where($userRoleTable.'.user_id', $user->getKey())
            ->where($permissionTable.'.slug', $permissionSlug)
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
            ->exists();
    }

    /**
     * Revisa si el usuario dispone de un permiso global (sin proyecto asociado).
     */
    public function userHasPermissionGlobal(Model $user, string $permissionSlug): bool
    {
        $permissionTable = famiq_permission_table_name('permissions');
        $rolePermissionTable = famiq_permission_table_name('role_permission');
        $userRoleTable = famiq_permission_table_name('user_role');

        return Permission::query()
            ->select($permissionTable.'.id')
            ->join($rolePermissionTable, $rolePermissionTable.'.permission_id', '=', $permissionTable.'.id')
            ->join($userRoleTable, $userRoleTable.'.role_id', '=', $rolePermissionTable.'.role_id')
            ->where($userRoleTable.'.user_id', $user->getKey())
            ->whereNull($userRoleTable.'.project_id')
            ->whereNull($permissionTable.'.project_id')
            ->where($permissionTable.'.slug', $permissionSlug)
            ->exists();
    }

    /**
     * Normaliza una referencia de proyecto devolviendo su identificador entero.
     *
     * @param  int|Model  $project
     */
    protected function resolveProjectId($project): int
    {
        if (is_numeric($project)) {
            return (int) $project;
        }

        if ($project instanceof Model) {
            return (int) $project->getKey();
        }

        throw new \InvalidArgumentException('Project reference is invalid.');
    }

    /**
     * Construye la consulta base que asocia usuarios y roles filtrados por slug.
     */
    protected function baseRoleQuery(Model $user, string $roleSlug): Builder
    {
        $userRoleTable = famiq_permission_table_name('user_role');

        return UserRole::query()
            ->where($userRoleTable.'.user_id', $user->getKey())
            ->whereHas('role', function (Builder $builder) use ($roleSlug): void {
                $builder->where('slug', $roleSlug);
            });
    }
}
