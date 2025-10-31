<?php

namespace Spatie\Permission\Contracts;

interface PermissionsProjectResolver
{
    public function getPermissionsProjectId(): int|string|null;

    /**
     * Set the project id for projects/groups support, this id is used when querying permissions/roles
     *
     * @param  int|string|\Illuminate\Database\Eloquent\Model|null  $id
     */
    public function setPermissionsProjectId($id): void;
}
