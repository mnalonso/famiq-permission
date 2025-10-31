<?php

namespace Spatie\Permission\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Spatie\Permission\Contracts\Permission as PermissionContract;
use Spatie\Permission\Contracts\Role as RoleContract;
use Symfony\Component\Console\Helper\TableCell;

class Show extends Command
{
    protected $signature = 'permission:show
            {guard? : The name of the guard}
            {style? : The display style (default|borderless|compact|box)}';

    protected $description = 'Show a table of roles and permissions per guard';

    public function handle()
    {
        $permissionClass = app(PermissionContract::class);
        $roleClass = app(RoleContract::class);
        $projectsEnabled = config('permission.projects');
        $project_key = config('permission.column_names.project_foreign_key');

        $style = $this->argument('style') ?? 'default';
        $guard = $this->argument('guard');

        if ($guard) {
            $guards = Collection::make([$guard]);
        } else {
            $guards = $permissionClass::pluck('guard_name')->merge($roleClass::pluck('guard_name'))->unique();
        }

        foreach ($guards as $guard) {
            $this->info("Guard: $guard");

            $roles = $roleClass::whereGuardName($guard)
                ->with('permissions')
                ->orderBy('name')
                ->get()
                ->flatMap(function ($role) use ($projectsEnabled, $project_key, $permissionClass) {
                    if (! $projectsEnabled) {
                        return [
                            $role->name => [
                                'permissions' => $role->permissions->pluck($permissionClass->getKeyName()),
                                $project_key => null,
                                'label' => $role->name,
                            ],
                        ];
                    }

                    $grouped = $role->permissions->groupBy($project_key);

                    if ($grouped->isEmpty()) {
                        $grouped = collect([null => collect()]);
                    }

                    return $grouped->mapWithKeys(function ($permissions, $projectId) use ($role, $permissionClass, $project_key) {
                        return [
                            $role->name.'_'.($projectId ?? 'global') => [
                                'permissions' => $permissions->pluck($permissionClass->getKeyName()),
                                $project_key => $projectId,
                                'label' => $role->name,
                            ],
                        ];
                    });
                });

            $permissions = $permissionClass::whereGuardName($guard)->orderBy('name')->pluck('name', $permissionClass->getKeyName());

            $body = $permissions->map(fn ($permission, $id) => $roles->map(
                fn (array $role_data) => $role_data['permissions']->contains($id) ? ' ✔' : ' ·'
            )->prepend($permission)
            );

            if ($projectsEnabled) {
                $projects = $roles->groupBy($project_key)->mapWithKeys(fn ($group, $id) => [
                    $id ?? 'global' => new TableCell('Project ID: '.($id ?? 'NULL'), ['colspan' => $group->count()]),
                ]);
            }

            $this->table(
                array_merge(
                    isset($projects) ? $projects->values()->prepend(new TableCell(''))->toArray() : [],
                    $roles->map(fn ($data) => $projectsEnabled ? sprintf('%s (Project: %s)', $data['label'], $data[$project_key] ?? 'global') : $data['label'])
                        ->prepend(new TableCell(''))->toArray(),
                ),
                $body->toArray(),
                $style
            );
        }
    }
}
