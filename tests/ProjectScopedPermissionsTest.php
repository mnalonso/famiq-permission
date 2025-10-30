<?php

namespace Spatie\Permission\Tests;

use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Group;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Project;
use Spatie\Permission\Models\Role;

class ProjectScopedPermissionsTest extends TestCase
{
    /** @var bool */
    protected $hasTeams = true;

    #[Test]
    public function roles_scope_permissions_to_the_current_project(): void
    {
        $projectA = Project::create(['description' => 'Project A']);
        $projectB = Project::create(['description' => 'Project B']);

        setPermissionsTeamId($projectA->id);
        $permission = Permission::create(['name' => 'approve-project']);
        $role = Role::create(['name' => 'project-manager']);
        $role->givePermissionTo($permission);
        $this->testUser->assignRole($role);

        setPermissionsTeamId($projectB->id);
        $permissionB = Permission::create(['name' => 'approve-project']);
        $roleB = Role::create(['name' => 'project-manager']);
        $roleB->givePermissionTo($permissionB);
        $this->testUser->assignRole($roleB);

        setPermissionsTeamId($projectA->id);
        $this->assertTrue($this->testUser->hasPermissionTo('approve-project'));
        $this->assertTrue($role->hasPermissionTo('approve-project'));
        $this->assertFalse($roleB->hasPermissionTo('approve-project'));

        setPermissionsTeamId($projectB->id);
        $this->assertTrue($this->testUser->hasPermissionTo('approve-project'));
        $this->assertTrue($roleB->hasPermissionTo('approve-project'));
        $this->assertFalse($role->hasPermissionTo('approve-project'));
    }

    #[Test]
    public function group_permissions_are_isolated_by_project(): void
    {
        $projectA = Project::create(['description' => 'Project A']);
        $projectB = Project::create(['description' => 'Project B']);

        $group = Group::create(['name' => 'Reviewers']);
        $this->testUser->assignGroup($group);

        setPermissionsTeamId($projectA->id);
        $permissionA = Permission::create(['name' => 'review-report']);
        $group->givePermissionTo($permissionA);

        setPermissionsTeamId($projectB->id);
        $permissionB = Permission::create(['name' => 'review-report']);
        $group->givePermissionTo($permissionB);

        setPermissionsTeamId($projectA->id);
        $this->assertTrue($this->testUser->hasPermissionTo('review-report'));
        $group->revokePermissionTo('review-report');
        $this->assertFalse($this->testUser->hasPermissionTo('review-report'));

        setPermissionsTeamId($projectB->id);
        $this->assertTrue($this->testUser->hasPermissionTo('review-report'));
    }
}
