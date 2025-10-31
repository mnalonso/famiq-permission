<?php

namespace Spatie\Permission;

use Spatie\Permission\Contracts\PermissionsProjectResolver;

class DefaultProjectResolver implements PermissionsProjectResolver
{
    protected int|string|null $projectId = null;

    /**
     * Set the project id for projects/groups support, this id is used when querying permissions/roles
     *
     * @param  int|string|\Illuminate\Database\Eloquent\Model|null  $id
     */
    public function setPermissionsProjectId($id): void
    {
        if ($id instanceof \Illuminate\Database\Eloquent\Model) {
            $id = $id->getKey();
        }
        $this->projectId = $id;
    }

    public function getPermissionsProjectId(): int|string|null
    {
        return $this->projectId;
    }
}
