<?php

declare(strict_types=1);

namespace Famiq\Permission\Tests\Unit;

use Famiq\Permission\Models\Permission;
use Famiq\Permission\Models\Role;
use Famiq\Permission\Tests\TestCase;

class RoleTest extends TestCase
{
    public function test_it_assigns_permission_instances(): void
    {
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin', 'scope' => 'global']);
        $permission = Permission::create(['name' => 'Ingresar', 'slug' => 'ingresar']);

        $role->givePermissionTo($permission);

        $this->assertDatabaseHas(famiq_permission_table_name('role_permission'), [
            'role_id' => $role->id,
            'permission_id' => $permission->id,
        ]);
    }

    public function test_it_accepts_permission_slugs_and_ids(): void
    {
        $role = Role::create(['name' => 'Manager', 'slug' => 'manager', 'scope' => 'project']);
        $login = Permission::create(['name' => 'Ingresar', 'slug' => 'ingresar']);
        $read = Permission::create(['name' => 'Leer', 'slug' => 'leer']);

        $role->givePermissionTo('ingresar', $read->id);

        $permissionTable = famiq_permission_table_name('permissions');
        $permissionIds = $role->permissions()->pluck($permissionTable.'.id')->all();

        $this->assertEqualsCanonicalizing([$login->id, $read->id], $permissionIds);
    }

    public function test_it_revokes_permission_instances(): void
    {
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin', 'scope' => 'global']);
        $login = Permission::create(['name' => 'Ingresar', 'slug' => 'ingresar']);
        $read = Permission::create(['name' => 'Leer', 'slug' => 'leer']);

        $role->givePermissionTo($login, $read);

        $role->revokePermissionTo($login);

        $this->assertDatabaseMissing(famiq_permission_table_name('role_permission'), [
            'role_id' => $role->id,
            'permission_id' => $login->id,
        ]);

        $this->assertDatabaseHas(famiq_permission_table_name('role_permission'), [
            'role_id' => $role->id,
            'permission_id' => $read->id,
        ]);
    }

    public function test_it_revokes_permissions_using_slugs_and_ids(): void
    {
        $role = Role::create(['name' => 'Manager', 'slug' => 'manager', 'scope' => 'project']);
        $login = Permission::create(['name' => 'Ingresar', 'slug' => 'ingresar']);
        $read = Permission::create(['name' => 'Leer', 'slug' => 'leer']);

        $role->givePermissionTo($login, $read);

        $role->revokePermissionTo('ingresar', $read->id);

        $this->assertDatabaseCount(famiq_permission_table_name('role_permission'), 0);
    }
}
