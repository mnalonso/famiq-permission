<?php

declare(strict_types=1);

namespace Famiq\Permission\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Famiq\Permission\Traits\HasRoles;

/**
 * Modelo de usuario mínimo usado en los tests.
 */
class User extends Authenticatable
{
    use HasRoles;

    protected $guarded = [];
}
