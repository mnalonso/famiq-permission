<?php

namespace Spatie\Permission\Tests;

use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Contracts\Role;
use Spatie\Permission\Tests\TestModels\User;

class ProjectHasRolesTest extends HasRolesTest
{
    /** @var bool */
    protected $hasProjects = true;

    /** @test */
    #[Test]
    public function it_deletes_pivot_table_entries_when_deleting_models()
    {
        $user1 = User::create(['email' => 'user2@test.com']);
        $user2 = User::create(['email' => 'user2@test.com']);

        setPermissionsProjectId(1);
        $user1->assignRole('testRole');
        $user1->givePermissionTo('edit-articles');
        $user2->assignRole('testRole');
        $user2->givePermissionTo('edit-articles');
        setPermissionsProjectId(2);
        $user1->givePermissionTo('edit-news');

        $this->assertDatabaseHas('model_has_permissions', [config('permission.column_names.model_morph_key') => $user1->id]);
        $this->assertDatabaseHas('model_has_roles', [config('permission.column_names.model_morph_key') => $user1->id]);

        $user1->delete();

        setPermissionsProjectId(1);
        $this->assertDatabaseMissing('model_has_permissions', [config('permission.column_names.model_morph_key') => $user1->id]);
        $this->assertDatabaseMissing('model_has_roles', [config('permission.column_names.model_morph_key') => $user1->id]);
        $this->assertDatabaseHas('model_has_permissions', [config('permission.column_names.model_morph_key') => $user2->id]);
        $this->assertDatabaseHas('model_has_roles', [config('permission.column_names.model_morph_key') => $user2->id]);
    }

    /** @test */
    #[Test]
    public function it_can_assign_same_and_different_roles_on_same_user_different_projects()
    {
        app(Role::class)->findOrCreate('testRole3');
        app(Role::class)->findOrCreate('testRole4'); // global role

        $testRole3 = app(Role::class)->where('name', 'testRole3')->first();
        $testRole4 = app(Role::class)->where('name', 'testRole4')->first();
        $this->assertNotNull($testRole3);
        $this->assertNotNull($testRole4);
        $this->assertEquals(1, app(Role::class)->where('name', 'testRole3')->count());

        setPermissionsProjectId(1);
        $this->testUser->assignRole('testRole', 'testRole2');

        // explicit load of roles to assert no mismatch
        // when same role assigned in diff projects
        // while old project's roles are loaded
        $this->testUser->load('roles');

        setPermissionsProjectId(2);
        $this->testUser->assignRole('testRole', 'testRole3');

        setPermissionsProjectId(1);
        $this->testUser->load('roles');

        $this->assertEquals(
            collect(['testRole', 'testRole2']),
            $this->testUser->getRoleNames()->sort()->values()
        );
        $this->assertTrue($this->testUser->hasExactRoles(['testRole', 'testRole2']));

        $this->testUser->assignRole('testRole3', 'testRole4');
        $this->assertTrue($this->testUser->hasExactRoles(['testRole', 'testRole2', 'testRole3', 'testRole4']));
        $this->assertTrue($this->testUser->hasRole($testRole3));
        $this->assertTrue($this->testUser->hasRole($testRole4));

        setPermissionsProjectId(2);
        $this->testUser->load('roles');

        $this->assertEquals(
            collect(['testRole', 'testRole3']),
            $this->testUser->getRoleNames()->sort()->values()
        );
        $this->assertTrue($this->testUser->hasExactRoles(['testRole', 'testRole3']));
        $this->assertTrue($this->testUser->hasRole($testRole3));
        $this->testUser->assignRole('testRole4');
        $this->assertTrue($this->testUser->hasExactRoles(['testRole', 'testRole3', 'testRole4']));
        $this->assertTrue($this->testUser->hasRole($testRole4));
    }

    /** @test */
    #[Test]
    public function it_can_sync_or_remove_roles_without_detach_on_different_projects()
    {
        app(Role::class)->findOrCreate('testRole3');

        setPermissionsProjectId(1);
        $this->testUser->syncRoles('testRole', 'testRole2');

        setPermissionsProjectId(2);
        $this->testUser->syncRoles('testRole', 'testRole3');

        setPermissionsProjectId(1);
        $this->testUser->load('roles');

        $this->assertEquals(
            collect(['testRole', 'testRole2']),
            $this->testUser->getRoleNames()->sort()->values()
        );

        $this->testUser->removeRole('testRole');
        $this->assertEquals(
            collect(['testRole2']),
            $this->testUser->getRoleNames()->sort()->values()
        );

        setPermissionsProjectId(2);
        $this->testUser->load('roles');

        $this->assertEquals(
            collect(['testRole', 'testRole3']),
            $this->testUser->getRoleNames()->sort()->values()
        );
    }

    /** @test */
    #[Test]
    public function it_can_scope_users_on_different_projects()
    {
        User::all()->each(fn ($item) => $item->delete());
        $user1 = User::create(['email' => 'user1@test.com']);
        $user2 = User::create(['email' => 'user2@test.com']);

        setPermissionsProjectId(2);
        $user1->assignRole($this->testUserRole);
        $user2->assignRole('testRole2');

        setPermissionsProjectId(1);
        $user1->assignRole('testRole');

        setPermissionsProjectId(2);
        $scopedUsers1Project1 = User::role($this->testUserRole)->get();
        $scopedUsers2Project1 = User::role(['testRole', 'testRole2'])->get();
        $scopedUsers3Project1 = User::withoutRole('testRole')->get();

        $this->assertEquals(1, $scopedUsers1Project1->count());
        $this->assertEquals(2, $scopedUsers2Project1->count());
        $this->assertEquals(1, $scopedUsers3Project1->count());

        setPermissionsProjectId(1);
        $scopedUsers1Project2 = User::role($this->testUserRole)->get();
        $scopedUsers2Project2 = User::role('testRole2')->get();
        $scopedUsers3Project2 = User::withoutRole('testRole')->get();

        $this->assertEquals(1, $scopedUsers1Project2->count());
        $this->assertEquals(0, $scopedUsers2Project2->count());
        $this->assertEquals(1, $scopedUsers3Project2->count());
    }
}
