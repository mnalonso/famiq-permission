<?php

declare(strict_types=1);

namespace Famiq\Permission\Tests\Feature;

use Famiq\Permission\Models\Permission;
use Famiq\Permission\Models\ProjectRole;
use Famiq\Permission\Models\Role;
use Famiq\Permission\Models\UserRole;
use Famiq\Permission\Tests\Fixtures\Project;
use Famiq\Permission\Tests\Fixtures\User;
use Famiq\Permission\Tests\TestCase;

/**
 * Suite focused on the helpers exposed by the HasRoles trait.
 */
class HasRolesTraitTest extends TestCase
{
    /**
     * Ensures rolesGlobal and rolesInProject scopes filter correctly.
     */
    public function test_trait_relationships_are_scoped(): void
    {
        $user = User::create([
            'name' => 'Trait User',
            'email' => 'trait@example.com',
            'password' => bcrypt('secret'),
        ]);

        $project = Project::create(['name' => 'Encuestas']);

        $globalRole = Role::create(['name' => 'Admin', 'slug' => 'admin', 'scope' => 'global']);
        $projectRole = Role::create(['name' => 'Gerente', 'slug' => 'gerente', 'scope' => 'project']);

        ProjectRole::create(['project_id' => $project->id, 'role_id' => $projectRole->id]);

        UserRole::create(['user_id' => $user->id, 'role_id' => $globalRole->id]);
        UserRole::create(['user_id' => $user->id, 'role_id' => $projectRole->id, 'project_id' => $project->id]);

        $globalSlugs = $user->rolesGlobal()->get()->pluck('slug')->all();
        $projectSlugs = $user->rolesInProject($project)->get()->pluck('slug')->all();

        $this->assertSame(['admin'], $globalSlugs);
        $this->assertSame(['gerente'], $projectSlugs);
    }

    /**
     * Confirms the trait throws an exception when an invalid project is received.
    */
    public function test_trait_throws_exception_with_invalid_project_reference(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = User::create([
            'name' => 'Trait User',
            'email' => 'trait-invalid@example.com',
            'password' => bcrypt('secret'),
        ]);

        $user->hasRoleInProject('admin', 'invalid-project');
    }

    /**
     * Validates HasRoles compatibility helpers.
     */
    public function test_trait_exposes_spatie_like_helpers(): void
    {
        $user = User::create([
            'name' => 'Trait User',
            'email' => 'helpers@example.com',
            'password' => bcrypt('secret'),
        ]);

        $project = Project::create(['name' => 'Encuestas']);

        $globalRole = Role::create(['name' => 'Admin', 'slug' => 'admin', 'scope' => 'global']);
        $projectRole = Role::create(['name' => 'Gerente', 'slug' => 'gerente', 'scope' => 'project']);

        $login = Permission::create(['name' => 'Ingresar', 'slug' => 'ingresar']);
        $read = Permission::create(['name' => 'Leer', 'slug' => 'leer', 'project_id' => $project->id]);

        $globalRole->permissions()->sync([$login->id]);
        $projectRole->permissions()->sync([$read->id]);

        ProjectRole::create(['project_id' => $project->id, 'role_id' => $projectRole->id]);

        $user->assignRole($globalRole);
        $user->assignRole($projectRole, $project);

        $this->assertTrue($user->hasPermissionTo('ingresar'));
        $this->assertTrue($user->hasPermissionTo('leer', $project));
        $this->assertTrue($user->hasAnyPermission(['ingresar', 'leer'], $project));
        $this->assertTrue($user->hasAllPermissions('ingresar'));
        $this->assertSame(['Admin', 'Gerente'], $user->getRoleNames()->all());

        $user->removeRole($globalRole);
        $this->assertFalse($user->hasPermissionTo('ingresar'));

        $user->syncRoles($projectRole, $project);
        $this->assertSame(['Gerente'], $user->getRoleNames()->all());
    }

    /**
     * Confirms the trait accepts arrays when assigning roles with a project context.
     */
    public function test_assign_role_accepts_arrays_with_project(): void
    {
        $user = User::create([
            'name' => 'Array User',
            'email' => 'array@example.com',
            'password' => bcrypt('secret'),
        ]);

        $project = Project::create(['name' => 'Encuestas']);

        $projectRole = Role::create(['name' => 'Gerente', 'slug' => 'gerente', 'scope' => 'project']);

        ProjectRole::create(['project_id' => $project->id, 'role_id' => $projectRole->id]);

        $user->assignRole([$projectRole], $project);
        $this->assertTrue($user->hasRoleInProject('gerente', $project));

        $user->removeRole([$projectRole], $project);
        $this->assertFalse($user->hasRoleInProject('gerente', $project));

        $user->syncRoles([$projectRole], $project);
        $this->assertTrue($user->hasRoleInProject('gerente', $project));
    }

    /**
     * Ensures getAllPermissions returns every reachable permission slug.
     */
    public function test_get_all_permissions_returns_all_slugs(): void
    {
        $user = User::create([
            'name' => 'Permissions User',
            'email' => 'permissions@example.com',
            'password' => bcrypt('secret'),
        ]);

        $projectA = Project::create(['name' => 'Encuestas']);
        $projectB = Project::create(['name' => 'Finanzas']);

        $globalRole = Role::create(['name' => 'Admin', 'slug' => 'admin', 'scope' => 'global']);
        $projectRole = Role::create(['name' => 'Gerente', 'slug' => 'gerente', 'scope' => 'project']);

        $login = Permission::create(['name' => 'Ingresar', 'slug' => 'ingresar']);
        $surveyRead = Permission::create(['name' => 'Leer', 'slug' => 'leer', 'project_id' => $projectA->id]);
        $financeRead = Permission::create(['name' => 'Consultar', 'slug' => 'consultar', 'project_id' => $projectB->id]);

        $globalRole->permissions()->sync([$login->id, $surveyRead->id]);
        $projectRole->permissions()->sync([$financeRead->id]);

        ProjectRole::create(['project_id' => $projectB->id, 'role_id' => $projectRole->id]);

        $user->assignRole($globalRole);
        $user->assignRole($projectRole, $projectB);

        $this->assertSame(
            ['consultar', 'ingresar', 'leer'],
            collect($user->getAllPermissions())->sort()->values()->all()
        );
    }
}
