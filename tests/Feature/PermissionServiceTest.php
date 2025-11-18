<?php

declare(strict_types=1);

namespace Famiq\Permission\Tests\Feature;

use Famiq\Permission\Models\Permission;
use Famiq\Permission\Models\ProjectRole;
use Famiq\Permission\Models\Role;
use Famiq\Permission\Models\UserRole;
use Famiq\Permission\Services\PermissionService;
use Famiq\Permission\Tests\Fixtures\Project;
use Famiq\Permission\Tests\Fixtures\User;
use Famiq\Permission\Tests\TestCase;

/**
 * Pruebas de integración del PermissionService y el trait HasProjectRoles.
 */
class PermissionServiceTest extends TestCase
{
    /**
     * Verifica el flujo feliz al consultar roles y permisos globales/proyecto.
     */
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

    /**
     * Garantiza que los permisos no se mezclen entre proyectos distintos.
     */
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

    /**
     * Comprueba el helper para consultar permisos sin especificar un proyecto.
     */
    public function test_it_checks_permissions_anywhere(): void
    {
        $user = User::create([
            'name' => 'Multi',
            'email' => 'multi@example.com',
            'password' => bcrypt('secret'),
        ]);

        $project = Project::create(['name' => 'Encuestas']);
        $otherProject = Project::create(['name' => 'Reportes']);

        $globalRole = Role::create(['name' => 'Admin', 'slug' => 'admin', 'scope' => 'global']);
        $projectRole = Role::create(['name' => 'Gerente Encuestas', 'slug' => 'gerente_encuestas', 'scope' => 'project']);
        $reportRole = Role::create(['name' => 'Reportes', 'slug' => 'report_manager', 'scope' => 'project']);

        $login = Permission::create(['name' => 'Ingresar', 'slug' => 'ingresar']);
        $readSurveys = Permission::create(['name' => 'Leer Encuestas', 'slug' => 'leer_encuestas', 'project_id' => $project->id]);
        $readReports = Permission::create(['name' => 'Leer Reportes', 'slug' => 'leer_reportes', 'project_id' => $otherProject->id]);

        $globalRole->permissions()->sync([$login->id]);
        $projectRole->permissions()->sync([$readSurveys->id]);
        $reportRole->permissions()->sync([$readReports->id]);

        ProjectRole::create(['project_id' => $project->id, 'role_id' => $projectRole->id]);
        ProjectRole::create(['project_id' => $otherProject->id, 'role_id' => $reportRole->id]);

        UserRole::create(['user_id' => $user->id, 'role_id' => $globalRole->id]);
        UserRole::create(['user_id' => $user->id, 'role_id' => $projectRole->id, 'project_id' => $project->id]);

        $service = app(PermissionService::class);

        $this->assertTrue($service->userHasPermission($user, 'ingresar'));
        $this->assertTrue($service->userHasPermission($user, 'leer_encuestas'));
        $this->assertTrue($user->canAnywhere('ingresar'));
        $this->assertTrue($user->canAnywhere('leer_encuestas'));

        $this->assertFalse($service->userHasPermission($user, 'leer_reportes'));
        $this->assertFalse($user->canAnywhere('leer_reportes'));
    }
}
