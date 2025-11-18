# Project Roles for Laravel

Package reusable para Laravel 10/11+ que implementa roles y permisos globales y por proyecto sin depender de `spatie/laravel-permission`. Incluye migrations publicables, config, modelos, trait para `User` y un servicio central para todas las comprobaciones.

## Instalación

```bash
composer require famiq/permission
```

Si tu app no usa auto discovery, registra el service provider y el alias manualmente:

```php
'providers' => [
    Famiq\Permission\PermissionServiceProvider::class,
],

'aliases' => [
    'FamiqPermission' => Famiq\Permission\Facades\FamiqPermission::class,
],
```

Publica el archivo de configuración y las migrations si querés personalizarlas:

```bash
php artisan vendor:publish --tag=famiq-permission-config
php artisan vendor:publish --tag=famiq-permission-migrations
```

El config `famiq-permission.php` te permite definir los modelos `User` y `Project`, las tablas externas (users/projects), prefijos de tablas del package y si se crean o no claves foráneas.

## Tablas que crea el package

1. `fp_roles`
2. `fp_permissions`
3. `fp_role_permission`
4. `fp_project_role`
5. `fp_user_role`

> Las tablas `users` y `projects` se asumen existentes en tu aplicación.

## Trait HasProjectRoles

Agrega el trait al modelo User configurado:

```php
use Famiq\Permission\Traits\HasProjectRoles;

class User extends Authenticatable
{
    use HasProjectRoles;
}
```

El trait expone:

```php
$user->hasRoleGlobal('admin');
$user->hasRoleInProject('gerente', $projectId);
$user->canGlobal('ingresar');
$user->canInProject('leer_encuestas', $project);
```

También deja disponibles las relaciones `userRoles`, `roles`, `rolesGlobal()` y `rolesInProject($project)`.

## Servicio central

`Famiq\Permission\Services\PermissionService` resuelve las comprobaciones evaluando roles globales, roles habilitados por proyecto, permisos generales y permisos específicos del proyecto solicitado.

Podés inyectar el servicio o usar la facade `FamiqPermission`:

```php
FamiqPermission::userHasPermissionInProject($user, 'leer_encuestas', $projectId);
```

## Ejemplo completo de uso

```php
use Famiq\Permission\Models\Permission;
use Famiq\Permission\Models\ProjectRole;
use Famiq\Permission\Models\Role;
use Famiq\Permission\Models\UserRole;
use App\Models\Project;
use App\Models\User;

$project = Project::create(['name' => 'Encuestas']);

$admin = Role::create(['name' => 'Admin', 'slug' => 'admin', 'scope' => 'global']);
$manager = Role::create(['name' => 'Gerente Encuestas', 'slug' => 'gerente_encuestas', 'scope' => 'project']);

$login = Permission::create(['name' => 'Ingresar', 'slug' => 'ingresar']);
$readSurveys = Permission::create(['name' => 'Leer Encuestas', 'slug' => 'leer_encuestas', 'project_id' => $project->id]);

$admin->permissions()->sync([$login->id]);
$manager->permissions()->sync([$login->id, $readSurveys->id]);

ProjectRole::create(['project_id' => $project->id, 'role_id' => $manager->id]);

$user = User::factory()->create();

UserRole::create(['user_id' => $user->id, 'role_id' => $admin->id]);
UserRole::create(['user_id' => $user->id, 'role_id' => $manager->id, 'project_id' => $project->id]);

$user->hasRoleGlobal('admin'); // true
$user->hasRoleInProject('gerente_encuestas', $project); // true

$user->canGlobal('ingresar'); // true
$user->canInProject('leer_encuestas', $project); // true
```

## Seeders sugeridos

1. Crear proyectos base (p.ej. Encuestas).
2. Crear permisos globales y específicos: `ingresar`, `leer_encuestas`, etc.
3. Crear roles y definir su scope (`global`, `project` o `both`).
4. Asociar permisos a roles (`role_permission`).
5. Habilitar los roles que se pueden usar en cada proyecto mediante `ProjectRole`.
6. Asignar roles globales y roles por proyecto a usuarios usando `UserRole` (con `project_id` null para globales).

## Tests

El repositorio incluye pruebas con Orchestra Testbench que verifican el servicio y el trait utilizando una base SQLite in-memory.

## Licencia

MIT
