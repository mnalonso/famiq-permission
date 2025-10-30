<?php

namespace Spatie\Permission\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Exceptions\GroupAlreadyExists;
use Spatie\Permission\Exceptions\GroupDoesNotExist;
use Spatie\Permission\Guard;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Traits\RefreshesPermissionCache;

/**
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 */
class Group extends Model
{
    use HasRoles;
    use RefreshesPermissionCache;

    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        $attributes['guard_name'] ??= Guard::getDefaultName(static::class);

        parent::__construct($attributes);

        $this->guarded[] = $this->primaryKey;
        $this->table = config('permission.table_names.groups') ?: parent::getTable();
    }

    /**
     * @throws GroupAlreadyExists
     */
    public static function create(array $attributes = [])
    {
        $attributes['guard_name'] ??= Guard::getDefaultName(static::class);

        $params = ['name' => $attributes['name'], 'guard_name' => $attributes['guard_name']];

        if (static::findByParam($params)) {
            throw GroupAlreadyExists::create($attributes['name'], $attributes['guard_name']);
        }

        return static::query()->create($attributes);
    }

    public function users(): BelongsToMany
    {
        return $this->morphedByMany(
            getModelForGuard($this->attributes['guard_name'] ?? config('auth.defaults.guard')),
            'model',
            config('permission.table_names.group_has_models'),
            config('permission.column_names.group_foreign_key', 'group_id'),
            config('permission.column_names.model_morph_key')
        );
    }

    /**
     * @throws GroupDoesNotExist
     */
    public static function findByName(string $name, ?string $guardName = null): self
    {
        $guardName ??= Guard::getDefaultName(static::class);

        $group = static::findByParam(['name' => $name, 'guard_name' => $guardName]);

        if (! $group) {
            throw GroupDoesNotExist::named($name, $guardName);
        }

        return $group;
    }

    /**
     * @throws GroupDoesNotExist
     */
    public static function findById(int|string $id, ?string $guardName = null): self
    {
        $guardName ??= Guard::getDefaultName(static::class);

        $group = static::findByParam([(new static)->getKeyName() => $id, 'guard_name' => $guardName]);

        if (! $group) {
            throw GroupDoesNotExist::withId($id, $guardName);
        }

        return $group;
    }

    public static function findOrCreate(string $name, ?string $guardName = null): self
    {
        $guardName ??= Guard::getDefaultName(static::class);

        $group = static::findByParam(['name' => $name, 'guard_name' => $guardName]);

        if (! $group) {
            return static::query()->create(['name' => $name, 'guard_name' => $guardName]);
        }

        return $group;
    }

    protected static function findByParam(array $params = []): ?self
    {
        $query = static::query();

        foreach ($params as $key => $value) {
            $query->where($key, $value);
        }

        return $query->first();
    }
}
