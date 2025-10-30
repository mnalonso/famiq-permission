<?php

namespace Spatie\Permission\Exceptions;

use InvalidArgumentException;

class GroupDoesNotExist extends InvalidArgumentException
{
    public static function named(string $groupName, ?string $guardName): self
    {
        return new static(__('There is no group named `:group` for guard `:guard`.', [
            'group' => $groupName,
            'guard' => $guardName,
        ]));
    }

    /**
     * @param  int|string  $groupId
     */
    public static function withId($groupId, ?string $guardName): self
    {
        return new static(__('There is no group with ID `:id` for guard `:guard`.', [
            'id' => $groupId,
            'guard' => $guardName,
        ]));
    }
}
