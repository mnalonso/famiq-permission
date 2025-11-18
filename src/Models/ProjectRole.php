<?php

declare(strict_types=1);

namespace Famiq\Permission\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pivot model enabling specific roles for each project.
 */
class ProjectRole extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'role_id',
    ];

    public function getTable()
    {
        return famiq_permission_table_name('project_role');
    }

    public function project(): BelongsTo
    {
        $projectModel = config('famiq-permission.project_model');

        return $this->belongsTo($projectModel, 'project_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}
