<?php

declare(strict_types=1);

namespace Famiq\Permission\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pivot model linking users with roles globally or per project.
 */
class UserRole extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'role_id',
        'project_id',
    ];

    /**
     * Nombre de la tabla pivot personalizada.
     */
    public function getTable(): string
    {
        return famiq_permission_table_name('user_role');
    }

    /**
     * Usuario dueño del registro pivot.
     */
    public function user(): BelongsTo
    {
        $userModel = config('famiq-permission.user_model');

        return $this->belongsTo($userModel, 'user_id');
    }

    /**
     * Rol asociado al usuario.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Proyecto asignado al rol si aplica.
     */
    public function project(): BelongsTo
    {
        $projectModel = config('famiq-permission.project_model');

        return $this->belongsTo($projectModel, 'project_id');
    }
}
