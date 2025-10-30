<?php

namespace Spatie\Permission\Traits;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Spatie\Permission\Exceptions\GuardDoesNotMatch;
use Spatie\Permission\Exceptions\GroupDoesNotExist;
use Spatie\Permission\Models\Group;
use Spatie\Permission\PermissionRegistrar;

trait HasGroups
{
    private ?string $groupClass = null;

    public static function bootHasGroups()
    {
        static::deleting(function ($model) {
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }

            $model->groups()->detach();
        });
    }

    public function getGroupClass(): string
    {
        if (! $this->groupClass) {
            $this->groupClass = config('permission.models.group');
        }

        return $this->groupClass;
    }

    public function groups(): MorphToMany
    {
        return $this->morphToMany(
            $this->getGroupClass(),
            'model',
            config('permission.table_names.group_has_models'),
            config('permission.column_names.model_morph_key'),
            config('permission.column_names.group_foreign_key', 'group_id')
        );
    }

    public function assignGroup(...$groups)
    {
        $groups = $this->collectGroups($groups);

        $model = $this->getModel();

        if ($model->exists) {
            $currentGroups = $this->groups->map(fn ($group) => $group->getKey())->toArray();

            $this->groups()->attach(array_diff($groups, $currentGroups));
            $model->unsetRelation('groups');
        } else {
            $class = \get_class($model);
            $saved = false;

            $class::saved(
                function ($object) use ($groups, $model, &$saved) {
                    if ($saved || $model->getKey() != $object->getKey()) {
                        return;
                    }

                    $model->groups()->attach($groups);
                    $model->unsetRelation('groups');
                    $saved = true;
                }
            );
        }

        return $this;
    }

    public function removeGroup(...$groups)
    {
        $storedGroups = Arr::wrap($groups);

        foreach ($storedGroups as $group) {
            $storedGroup = $this->getStoredGroup($group);
            $this->groups()->detach($storedGroup);
        }

        $this->unsetRelation('groups');

        return $this;
    }

    public function syncGroups(...$groups)
    {
        $groups = $this->collectGroups($groups);

        $this->groups()->sync($groups);
        $this->unsetRelation('groups');

        return $this;
    }

    public function hasGroup($group): bool
    {
        if ($group instanceof Group) {
            return $this->groups->contains(fn ($value) => $value->is($group));
        }

        if ($group instanceof \BackedEnum) {
            $group = $group->value;
        }

        return $this->groups->contains('name', $group);
    }

    public function hasAnyGroup(...$groups): bool
    {
        foreach (Arr::flatten($groups) as $group) {
            if ($this->hasGroup($group)) {
                return true;
            }
        }

        return false;
    }

    public function hasAllGroups(...$groups): bool
    {
        foreach (Arr::flatten($groups) as $group) {
            if (! $this->hasGroup($group)) {
                return false;
            }
        }

        return true;
    }

    public function getGroupNames(): Collection
    {
        return $this->groups->pluck('name');
    }

    protected function collectGroups(...$groups): array
    {
        return collect($groups)
            ->flatten()
            ->reduce(function ($array, $group) {
                if (empty($group)) {
                    return $array;
                }

                $group = $this->getStoredGroup($group);

                if (! in_array($group->getKey(), $array)) {
                    $this->ensureGroupSharesGuard($group);
                    $array[] = $group->getKey();
                }

                return $array;
            }, []);
    }

    protected function getStoredGroup($group)
    {
        if ($group instanceof Group) {
            return $group;
        }

        $groupClass = $this->getGroupClass();

        if ($group instanceof \BackedEnum) {
            $group = $group->value;
        }

        if (is_int($group) || PermissionRegistrar::isUid($group)) {
            return $groupClass::findById($group, $this->getDefaultGuardName());
        }

        if (is_string($group)) {
            return $groupClass::findByName($group, $this->getDefaultGuardName());
        }

        throw GroupDoesNotExist::named(is_scalar($group) ? (string) $group : '', $this->getDefaultGuardName());
    }

    protected function ensureGroupSharesGuard(Group $group): void
    {
        if (! $this->getGuardNames()->contains($group->guard_name)) {
            throw GuardDoesNotMatch::create($group->guard_name, $this->getGuardNames());
        }
    }
}
