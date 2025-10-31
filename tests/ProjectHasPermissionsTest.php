<?php

namespace Spatie\Permission\Tests;

use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Tests\TestModels\User;

class ProjectHasPermissionsTest extends HasPermissionsTest
{
    /** @var bool */
    protected $hasProjects = true;

    /** @test */
    #[Test]
    public function it_allows_assigning_direct_permissions_without_a_project()
    {
        setPermissionsProjectId(null);

        $this->testUser->givePermissionTo('edit-articles');

        $this->assertTrue($this->testUser->hasDirectPermission('edit-articles'));
        $this->assertDatabaseHas('model_has_permissions', [
            config('permission.column_names.model_morph_key') => $this->testUser->getKey(),
            app(\Spatie\Permission\PermissionRegistrar::class)->pivotPermission => $this->testUserPermission->getKey(),
            config('permission.column_names.project_foreign_key') => null,
        ]);
    }

    /** @test */
    #[Test]
    public function it_returns_all_direct_permissions_when_no_project_is_selected()
    {
        setPermissionsProjectId(1);
        $this->testUser->givePermissionTo('edit-articles');

        setPermissionsProjectId(2);
        $this->testUser->givePermissionTo('edit-blog');

        setPermissionsProjectId(null);
        $this->testUser->load('permissions');

        $this->assertEquals(
            collect(['edit-articles', 'edit-blog'])->sort()->values(),
            $this->testUser->permissions->pluck('name')->sort()->values()
        );
    }

    /** @test */
    #[Test]
    public function it_can_assign_same_and_different_permission_on_same_user_on_different_projects()
    {
        setPermissionsProjectId(1);
        $this->testUser->givePermissionTo('edit-articles', 'edit-news');

        setPermissionsProjectId(2);
        $this->testUser->givePermissionTo('edit-articles', 'edit-blog');

        setPermissionsProjectId(1);
        $this->testUser->load('permissions');
        $this->assertEquals(
            collect(['edit-articles', 'edit-news']),
            $this->testUser->getPermissionNames()->sort()->values()
        );
        $this->assertTrue($this->testUser->hasAllDirectPermissions(['edit-articles', 'edit-news']));
        $this->assertFalse($this->testUser->hasAllDirectPermissions(['edit-articles', 'edit-blog']));

        setPermissionsProjectId(2);
        $this->testUser->load('permissions');
        $this->assertEquals(
            collect(['edit-articles', 'edit-blog']),
            $this->testUser->getPermissionNames()->sort()->values()
        );
        $this->assertTrue($this->testUser->hasAllDirectPermissions(['edit-articles', 'edit-blog']));
        $this->assertFalse($this->testUser->hasAllDirectPermissions(['edit-articles', 'edit-news']));
    }

    /** @test */
    #[Test]
    public function it_can_list_all_the_coupled_permissions_both_directly_and_via_roles_on_same_user_on_different_projects()
    {
        $this->testUserRole->givePermissionTo('edit-articles');

        setPermissionsProjectId(1);
        $this->testUser->assignRole('testRole');
        $this->testUser->givePermissionTo('edit-news');

        setPermissionsProjectId(2);
        $this->testUser->assignRole('testRole');
        $this->testUser->givePermissionTo('edit-blog');

        setPermissionsProjectId(1);
        $this->testUser->load('roles', 'permissions');

        $this->assertEquals(
            collect(['edit-articles', 'edit-news']),
            $this->testUser->getAllPermissions()->pluck('name')->sort()->values()
        );

        setPermissionsProjectId(2);
        $this->testUser->load('roles', 'permissions');

        $this->assertEquals(
            collect(['edit-articles', 'edit-blog']),
            $this->testUser->getAllPermissions()->pluck('name')->sort()->values()
        );
    }

    /** @test */
    #[Test]
    public function it_can_sync_or_remove_permission_without_detach_on_different_projects()
    {
        setPermissionsProjectId(1);
        $this->testUser->syncPermissions('edit-articles', 'edit-news');

        setPermissionsProjectId(2);
        $this->testUser->syncPermissions('edit-articles', 'edit-blog');

        setPermissionsProjectId(1);
        $this->testUser->load('permissions');

        $this->assertEquals(
            collect(['edit-articles', 'edit-news']),
            $this->testUser->getPermissionNames()->sort()->values()
        );

        $this->testUser->revokePermissionTo('edit-articles');
        $this->assertEquals(
            collect(['edit-news']),
            $this->testUser->getPermissionNames()->sort()->values()
        );

        setPermissionsProjectId(2);
        $this->testUser->load('permissions');
        $this->assertEquals(
            collect(['edit-articles', 'edit-blog']),
            $this->testUser->getPermissionNames()->sort()->values()
        );
    }

    /** @test */
    #[Test]
    public function it_can_scope_users_on_different_projects()
    {
        $user1 = User::create(['email' => 'user1@test.com']);
        $user2 = User::create(['email' => 'user2@test.com']);

        setPermissionsProjectId(2);
        $user1->givePermissionTo(['edit-articles', 'edit-news']);
        $this->testUserRole->givePermissionTo('edit-articles');
        $user2->assignRole('testRole');

        setPermissionsProjectId(1);
        $user1->givePermissionTo(['edit-articles']);

        setPermissionsProjectId(2);
        $scopedUsers1Project2 = User::permission(['edit-articles', 'edit-news'])->get();
        $scopedUsers2Project2 = User::permission('edit-news')->get();

        $this->assertEquals(2, $scopedUsers1Project2->count());
        $this->assertEquals(1, $scopedUsers2Project2->count());

        setPermissionsProjectId(1);
        $scopedUsers1Project1 = User::permission(['edit-articles', 'edit-news'])->get();
        $scopedUsers2Project1 = User::permission('edit-news')->get();

        $this->assertEquals(1, $scopedUsers1Project1->count());
        $this->assertEquals(0, $scopedUsers2Project1->count());
    }
}
