<?php

namespace Spatie\Permission\Tests;

use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Group;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class GroupTest extends TestCase
{
    #[Test]
    public function users_inherit_permissions_assigned_directly_to_groups(): void
    {
        $group = Group::create(['name' => 'Editors']);
        Permission::findOrCreate('edit-articles');

        $this->testUser->assignGroup($group);
        $group->givePermissionTo('edit-articles');

        $this->assertTrue($this->testUser->hasPermissionTo('edit-articles'));
    }

    #[Test]
    public function users_inherit_permissions_assigned_via_group_roles(): void
    {
        $group = Group::create(['name' => 'Managers']);
        $role = Role::findOrCreate('team-lead');
        Permission::findOrCreate('approve-leave');

        $group->assignRole($role);
        $role->givePermissionTo('approve-leave');

        $this->testUser->assignGroup($group);

        $this->assertTrue($this->testUser->hasPermissionTo('approve-leave'));
    }

    #[Test]
    public function groups_can_be_synced_without_affecting_other_memberships(): void
    {
        $alpha = Group::create(['name' => 'Alpha']);
        $beta = Group::create(['name' => 'Beta']);

        $this->testUser->assignGroup($alpha, $beta);
        $this->assertTrue($this->testUser->hasAllGroups('Alpha', 'Beta'));

        $this->testUser->syncGroups('Alpha');

        $this->assertTrue($this->testUser->hasGroup('Alpha'));
        $this->assertFalse($this->testUser->hasGroup('Beta'));

        $this->testUser->removeGroup('Alpha');

        $this->assertFalse($this->testUser->hasAnyGroup(['Alpha', 'Beta']));
    }
}
