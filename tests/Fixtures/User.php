<?php

declare(strict_types=1);

namespace Famiq\Permission\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Famiq\Permission\Traits\HasProjectRoles;

class User extends Authenticatable
{
    use HasProjectRoles;

    protected $guarded = [];
}
