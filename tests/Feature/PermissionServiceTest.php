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

class PermissionServiceTest extends TestCase
{
    public function test_it_checks_global_and_project_permissions(): void
    {
        $user = User::create([
            'name' => 'Test',
            'email' => 'user@example.com',
            'password' => bcrypt('secret'),
        ]);

        $project = Project::create(['name' => 'Encuestas']);

        $adminRole = Role::create(['name' => 'Admin', 'slug' => 'admin', 'scope' => 'global']);
        $managerRole = Role::create(['name' => 'Gerente Encuestas', 'slug' => 'gerente_encuestas', 'scope' => 'project']);

        $login = Permission::create(['name' => 'Ingresar', 'slug' => 'ingresar']);
        $read = Permission::create(['name' => 'Leer Encuestas', 'slug' => 'leer_encuestas', 'project_id' => $project->id]);

        $adminRole->permissions()->sync([$login->id]);
        $managerRole->permissions()->sync([$login->id, $read->id]);

        ProjectRole::create(['project_id' => $project->id, 'role_id' => $managerRole->id]);

        UserRole::create(['user_id' => $user->id, 'role_id' => $adminRole->id]);
        UserRole::create(['user_id' => $user->id, 'role_id' => $managerRole->id, 'project_id' => $project->id]);

        $this->assertTrue($user->hasRoleGlobal('admin'));
        $this->assertTrue($user->hasRoleInProject('gerente_encuestas', $project));
        $this->assertTrue($user->canGlobal('ingresar'));
        $this->assertTrue($user->canInProject('leer_encuestas', $project));
    }

    public function test_permissions_are_scoped_to_projects(): void
    {
        $user = User::create([
            'name' => 'Test',
            'email' => 'scoped@example.com',
            'password' => bcrypt('secret'),
        ]);

        $project = Project::create(['name' => 'Encuestas']);
        $otherProject = Project::create(['name' => 'Reportes']);

        $role = Role::create(['name' => 'Gerente Encuestas', 'slug' => 'gerente_encuestas', 'scope' => 'project']);
        $permission = Permission::create(['name' => 'Leer Encuestas', 'slug' => 'leer_encuestas', 'project_id' => $project->id]);
        $role->permissions()->sync([$permission->id]);

        ProjectRole::create(['project_id' => $project->id, 'role_id' => $role->id]);

        UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, 'project_id' => $project->id]);

        $this->assertTrue($user->hasRoleInProject('gerente_encuestas', $project));
        $this->assertTrue($user->canInProject('leer_encuestas', $project));

        $this->assertFalse($user->hasRoleInProject('gerente_encuestas', $otherProject));
        $this->assertFalse($user->canInProject('leer_encuestas', $otherProject));
    }
}
