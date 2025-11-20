<?php

declare(strict_types=1);

namespace Famiq\Permission\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $table = "projects";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
    ];

    /**
     * Devuelve la tabla con prefijo configurado.
     */
    public function getTable(): string
    {
        return famiq_permission_table_name('projects');
    }
}
