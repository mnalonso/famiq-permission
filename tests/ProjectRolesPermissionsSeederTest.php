<?php

namespace Spatie\Permission\Tests;

use Database\Seeders\ProjectRolesPermissionsSeeder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Tests\TestModels\User;

class ProjectRolesPermissionsSeederTest extends TestCase
{
    protected bool $hasProjects = true;

    #[Test]
    public function it_seeds_roles_and_permissions_for_each_project(): void
    {
        (new ProjectRolesPermissionsSeeder())->run();

        $projectKey = app(PermissionRegistrar::class)->projectsKey;
        $projectsTable = config('permission.table_names.projects');

        $this->assertEquals(
            [
                'A' => 'Proyecto A',
                'B' => 'Proyecto B',
                'C' => 'Proyecto C',
                'D' => 'Proyecto D',
            ],
            DB::table($projectsTable)->pluck('name', 'code')->all()
        );

        foreach (ProjectRolesPermissionsSeeder::PROJECT_DEFINITIONS as $definition) {
            $projectId = $definition['id'];

            $this->assertEqualsCanonicalizing(
                $definition['permissions'],
                Permission::query()
                    ->where($projectKey, $projectId)
                    ->where('guard_name', 'web')
                    ->pluck('name')
                    ->all()
            );

            foreach ($definition['roles'] as $roleName => $permissionNames) {
                $role = Role::query()
                    ->where('name', $roleName)
                    ->where($projectKey, $projectId)
                    ->where('guard_name', 'web')
                    ->first();

                $this->assertNotNull($role, sprintf('Role %s for project %s was not seeded', $roleName, $definition['label']));

                $this->assertEqualsCanonicalizing(
                    $permissionNames,
                    $role->permissions->pluck('name')->all(),
                    sprintf('Unexpected permissions for role %s in %s', $roleName, $definition['label'])
                );
            }
        }
    }

    #[Test]
    public function seeded_roles_enforce_project_specific_permissions(): void
    {
        (new ProjectRolesPermissionsSeeder())->run();

        $projectKey = app(PermissionRegistrar::class)->projectsKey;
        $user = User::create(['email' => 'gestor-proyectos@example.com']);

        $projectADef = ProjectRolesPermissionsSeeder::PROJECT_DEFINITIONS['A'];
        $projectBDef = ProjectRolesPermissionsSeeder::PROJECT_DEFINITIONS['B'];
        $projectDDef = ProjectRolesPermissionsSeeder::PROJECT_DEFINITIONS['D'];

        $gestorA = Role::query()
            ->where('name', 'Gestor')
            ->where($projectKey, $projectADef['id'])
            ->firstOrFail();

        \setPermissionsProjectId($projectADef['id']);
        $user->assignRole($gestorA);

        $this->assertTrue($user->hasPermissionTo('leer listas Proyecto A'));
        $this->assertTrue($user->hasPermissionTo('exportar tableros Proyecto A'));
        $this->assertFalse($user->hasPermissionTo('escribir listas Proyecto B'));

        $colaboradorB = Role::query()
            ->where('name', 'Colaborador')
            ->where($projectKey, $projectBDef['id'])
            ->firstOrFail();

        \setPermissionsProjectId($projectBDef['id']);
        $user->assignRole($colaboradorB);

        $this->assertTrue($user->hasPermissionTo('escribir listas Proyecto B'));
        $this->assertFalse($user->hasPermissionTo('gestionar presupuesto Proyecto D'));

        $colaboradorD = Role::query()
            ->where('name', 'Colaborador')
            ->where($projectKey, $projectDDef['id'])
            ->firstOrFail();

        \setPermissionsProjectId($projectDDef['id']);
        $user->assignRole($colaboradorD);

        $this->assertTrue($user->hasPermissionTo('ver métricas Proyecto D'));
        $this->assertFalse($user->hasPermissionTo('gestionar presupuesto Proyecto D'));
    }
}
