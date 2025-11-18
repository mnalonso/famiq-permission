<?php

declare(strict_types=1);

namespace Famiq\Permission\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Role entity that can be assigned globally or per project.
 */
class Role extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'scope',
    ];

    public function getTable()
    {
        return famiq_permission_table_name('roles');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            famiq_permission_table_name('role_permission'),
            'role_id',
            'permission_id'
        );
    }

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

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class, 'role_id');
    }
}
