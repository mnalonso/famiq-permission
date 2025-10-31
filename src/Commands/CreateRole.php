<?php

namespace Spatie\Permission\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Contracts\Permission as PermissionContract;
use Spatie\Permission\Contracts\Role as RoleContract;
use Spatie\Permission\PermissionRegistrar;

class CreateRole extends Command
{
    protected $signature = 'permission:create-role
        {name : The name of the role}
        {guard? : The name of the guard}
        {permissions? : A list of permissions to assign to the role, separated by | }
        {--project-id=}';

    protected $description = 'Create a role';

    public function handle(PermissionRegistrar $permissionRegistrar)
    {
        $roleClass = app(RoleContract::class);

        $targetProjectId = $this->option('project-id');
        $originalProjectId = getPermissionsProjectId();

        if (! $permissionRegistrar->projects && $targetProjectId) {
            $this->warn('Projects feature disabled, argument --project-id has no effect. Either enable it in permissions config file or remove --project-id parameter');

            return;
        }

        if ($targetProjectId !== null) {
            setPermissionsProjectId($targetProjectId);
        }

        $role = $roleClass::findOrCreate($this->argument('name'), $this->argument('guard'));

        $role->givePermissionTo($this->makePermissions($this->argument('permissions')));

        setPermissionsProjectId($originalProjectId);

        $this->info("Role `{$role->name}` ".($role->wasRecentlyCreated ? 'created' : 'updated'));
    }

    /**
     * @param  array|null|string  $string
     */
    protected function makePermissions($string = null)
    {
        if (empty($string)) {
            return;
        }

        $permissionClass = app(PermissionContract::class);

        $permissions = explode('|', $string);

        $models = [];

        foreach ($permissions as $permission) {
            $models[] = $permissionClass::findOrCreate(trim($permission), $this->argument('guard'));
        }

        return collect($models);
    }
}
