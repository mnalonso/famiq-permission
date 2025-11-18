<?php

declare(strict_types=1);

namespace Famiq\Permission\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Permission that can be scoped to a project or be global.
 */
class Permission extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'project_id',
    ];

    public function getTable()
    {
        return famiq_permission_table_name('permissions');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            famiq_permission_table_name('role_permission'),
            'permission_id',
            'role_id'
        );
    }

    public function project(): BelongsTo
    {
        $projectModel = config('famiq-permission.project_model');

        return $this->belongsTo($projectModel, 'project_id');
    }
}
