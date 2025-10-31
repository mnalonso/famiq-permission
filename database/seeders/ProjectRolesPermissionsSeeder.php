<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ProjectRolesPermissionsSeeder extends Seeder
{
    /**
     * Project definitions grouped by project code.
     */
    public const PROJECT_DEFINITIONS = [
        'A' => [
            'id' => 1,
            'label' => 'Proyecto A',
            'permissions' => [
                'leer listas Proyecto A',
                'aprobar listas Proyecto A',
                'exportar tableros Proyecto A',
            ],
            'roles' => [
                'Gestor' => [
                    'leer listas Proyecto A',
                    'aprobar listas Proyecto A',
                    'exportar tableros Proyecto A',
                ],
                'Colaborador' => [
                    'leer listas Proyecto A',
                    'exportar tableros Proyecto A',
                ],
            ],
        ],
        'B' => [
            'id' => 2,
            'label' => 'Proyecto B',
            'permissions' => [
                'escribir listas Proyecto B',
                'leer listas Proyecto B',
                'compartir listas Proyecto B',
            ],
            'roles' => [
                'Gestor' => [
                    'escribir listas Proyecto B',
                    'leer listas Proyecto B',
                    'compartir listas Proyecto B',
                ],
                'Colaborador' => [
                    'escribir listas Proyecto B',
                    'leer listas Proyecto B',
                ],
            ],
        ],
        'C' => [
            'id' => 3,
            'label' => 'Proyecto C',
            'permissions' => [
                'planificar hitos Proyecto C',
                'registrar avances Proyecto C',
                'marcar bloqueos Proyecto C',
            ],
            'roles' => [
                'Gestor' => [
                    'planificar hitos Proyecto C',
                    'registrar avances Proyecto C',
                    'marcar bloqueos Proyecto C',
                ],
                'Colaborador' => [
                    'registrar avances Proyecto C',
                    'marcar bloqueos Proyecto C',
                ],
            ],
        ],
        'D' => [
            'id' => 4,
            'label' => 'Proyecto D',
            'permissions' => [
                'gestionar presupuesto Proyecto D',
                'ver métricas Proyecto D',
                'reportar incidencias Proyecto D',
            ],
            'roles' => [
                'Gestor' => [
                    'gestionar presupuesto Proyecto D',
                    'ver métricas Proyecto D',
                    'reportar incidencias Proyecto D',
                ],
                'Colaborador' => [
                    'ver métricas Proyecto D',
                    'reportar incidencias Proyecto D',
                ],
            ],
        ],
    ];

    public function run(): void
    {
        /** @var PermissionRegistrar $registrar */
        $registrar = app(PermissionRegistrar::class);
        $projectKey = $registrar->projectsKey;

        $registrar->forgetCachedPermissions();

        $tableNames = config('permission.table_names');
        $projectsTable = $tableNames['projects'] ?? null;

        if ($projectsTable && Schema::hasTable($projectsTable)) {
            $now = now();
            $projects = [];

            foreach (self::PROJECT_DEFINITIONS as $code => $definition) {
                $projects[] = [
                    'id' => $definition['id'],
                    'code' => $code,
                    'name' => $definition['label'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table($projectsTable)->upsert(
                $projects,
                ['id'],
                ['code', 'name', 'updated_at']
            );
        }

        foreach (self::PROJECT_DEFINITIONS as $definition) {
            $projectId = $definition['id'];

            \setPermissionsProjectId($projectId);

            foreach ($definition['permissions'] as $permissionName) {
                Permission::query()->firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                    $projectKey => $projectId,
                ]);
            }

            foreach ($definition['roles'] as $roleName => $permissionNames) {
                $role = Role::query()->firstOrCreate([
                    'name' => $roleName,
                    'guard_name' => 'web',
                    $projectKey => $projectId,
                ]);

                $role->syncPermissions($permissionNames);
            }
        }

        $registrar->forgetCachedPermissions();

        \setPermissionsProjectId(null);
    }
}
