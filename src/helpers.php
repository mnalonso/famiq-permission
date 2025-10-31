<?php

if (! function_exists('getModelForGuard')) {
    function getModelForGuard(string $guard): ?string
    {
        return Spatie\Permission\Guard::getModelForGuard($guard);
    }

}

if (! function_exists('setPermissionsProjectId')) {
    /**
     * @param  int|string|null|\Illuminate\Database\Eloquent\Model  $id
     */
    function setPermissionsProjectId($id)
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsProjectId($id);
    }
}

if (! function_exists('getPermissionsProjectId')) {
    /**
     * @return int|string|null
     */
    function getPermissionsProjectId()
    {
        return app(\Spatie\Permission\PermissionRegistrar::class)->getPermissionsProjectId();
    }
}
