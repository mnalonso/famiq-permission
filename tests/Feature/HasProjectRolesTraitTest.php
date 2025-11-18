<?php

declare(strict_types=1);

namespace Famiq\Permission\Tests\Feature;

use Famiq\Permission\Models\ProjectRole;
use Famiq\Permission\Models\Role;
use Famiq\Permission\Models\UserRole;
use Famiq\Permission\Tests\Fixtures\Project;
use Famiq\Permission\Tests\Fixtures\User;
use Famiq\Permission\Tests\TestCase;

/**
 * Suite enfocada en los helpers expuestos por el trait HasProjectRoles.
 */
class HasProjectRolesTraitTest extends TestCase
{
    /**
     * Comprueba que los scopes rolesGlobal y rolesInProject filtren correctamente.
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
     * Confirma que el trait arroje una excepción al recibir un proyecto inválido.
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
}
