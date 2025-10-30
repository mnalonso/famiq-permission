<?php

namespace Spatie\Permission\Exceptions;

use InvalidArgumentException;

class GroupAlreadyExists extends InvalidArgumentException
{
    public static function create(string $groupName, string $guardName): self
    {
        return new static(__('A group `:group` already exists for guard `:guard`.', [
            'group' => $groupName,
            'guard' => $guardName,
        ]));
    }
}
