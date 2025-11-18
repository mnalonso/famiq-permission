<?php

declare(strict_types=1);

namespace Famiq\Permission\Facades;

use Illuminate\Support\Facades\Facade;
use Famiq\Permission\Services\PermissionService;

/**
 * @method static bool userHasRoleInProject(\Illuminate\Database\Eloquent\Model $user, string $roleSlug, $project = null)
 * @method static bool userHasPermissionInProject(\Illuminate\Database\Eloquent\Model $user, string $permissionSlug, $project)
 * @method static bool userHasRoleGlobal(\Illuminate\Database\Eloquent\Model $user, string $roleSlug)
 * @method static bool userHasPermissionGlobal(\Illuminate\Database\Eloquent\Model $user, string $permissionSlug)
 */
class FamiqPermission extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PermissionService::class;
    }
}
