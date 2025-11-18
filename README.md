# famiq/permission

Package Laravel 10/11+ que implementa un sistema de roles y permisos globales y por proyecto totalmente desacoplado de `spatie/laravel-permission`. Incluye migrations publicables con prefijo `fp_`, archivo de configuración propio, modelos Eloquent del vendor, un trait listo para el modelo `User` de tu aplicación y un servicio central que resuelve todas las comprobaciones de autorizaciones.

## Características clave

- Roles globales y roles específicos de proyectos con scopes `global`, `project` o `both`.
- Permisos globales (sin proyecto) y permisos exclusivos por proyecto.
- Tabla `project_role` para controlar qué roles están habilitados en cada proyecto.
- Servicio `PermissionService` y facade `FamiqPermission` con métodos `hasRole*` y `can*`.
- Trait `HasProjectRoles` para exponer helpers directamente desde tu modelo `User`.
- Configuración flexible para modelos externos (`User`, `Project`) y nombres de tablas.
- Migrations publicables con prefijo configurable (por defecto `fp_`).
- Pruebas integradas con Orchestra Testbench como referencia de uso.

## Requisitos

- PHP 8.2+
- Laravel 10.x o 11.x

## Instalación paso a paso

1. **Instala el package**:
   ```bash
   composer require famiq/permission
   ```

2. **Registra el Service Provider y la Facade** (solo si tu app no usa auto-discovery):
   ```php
   // config/app.php
   'providers' => [
       // ...
       Famiq\Permission\PermissionServiceProvider::class,
   ],

   'aliases' => [
       // ...
       'FamiqPermission' => Famiq\Permission\Facades\FamiqPermission::class,
   ],
   ```

3. **Publica el archivo de configuración y las migrations** para ajustarlas según tus modelos/tablas:
   ```bash
   php artisan vendor:publish --tag=famiq-permission-config
   php artisan vendor:publish --tag=famiq-permission-migrations
   ```

4. **Ejecuta las migrations** después de revisarlas o adaptarlas:
   ```bash
   php artisan migrate
   ```

## Configuración (`config/famiq-permission.php`)

El archivo publicado incluye:

- `user_model`: clase del modelo User (por defecto `App\Models\User`).
- `project_model`: clase del modelo Project (por defecto `App\Models\Project`).
- `tables`: nombres de las tablas externas `users` y `projects` si usás aliases distintos.
- `table_prefix`: prefijo usado para las tablas del package (`fp_` por defecto).
- `foreign_keys`: habilitar/deshabilitar restricciones de clave foránea.

Modifica estos valores si tu dominio usa otras clases o nombres físicos en la BD.

## Tablas creadas por el package

Asumiendo el prefijo por defecto `fp_`, se generan:

1. `fp_roles`
2. `fp_permissions`
3. `fp_role_permission`
4. `fp_project_role`
5. `fp_user_role`

> Las tablas `users` y `projects` deben existir en tu aplicación. El package solo referencia sus claves primarias a través de la configuración.

## Modelos Eloquent del vendor

- `Famiq\Permission\Models\Role`
- `Famiq\Permission\Models\Permission`
- `Famiq\Permission\Models\ProjectRole`
- `Famiq\Permission\Models\UserRole`

Estos modelos ya incluyen las relaciones necesarias (`permissions`, `projects`, `userRoles`, etc.) para administrar los datos desde seeders o paneles de administración.

## Trait `HasProjectRoles`

Agrega el trait al modelo `User` de tu app para obtener relaciones y helpers:

```php
use Famiq\Permission\Traits\HasProjectRoles;

class User extends Authenticatable
{
    use HasProjectRoles;
}
```

### Helpers disponibles

```php
$user->hasRoleGlobal('admin');
$user->hasRoleInProject('gerente_encuestas', $project); // acepta instancia, ID o clave

$user->canGlobal('ingresar');
$user->canInProject('leer_encuestas', $projectId);
$user->canAnywhere('leer_encuestas');
```

Además se exponen las relaciones:

- `userRoles()` → asignaciones individuales (globales o por proyecto).
- `roles()` → roles globales y por proyecto.
- `rolesGlobal()`
- `rolesInProject($project)`
- `canAnywhere($permission)` → verifica el permiso sin indicar proyecto (global o en cualquiera de los proyectos habilitados).

Internamente el trait delega toda la lógica en `PermissionService`, lo que facilita testear e inyectar el comportamiento en otros servicios.

## Servicio y Facade

El servicio central es `Famiq\Permission\Services\PermissionService`. Podés inyectarlo o utilizar la facade `FamiqPermission`:

```php
use Famiq\Permission\Facades\FamiqPermission;

FamiqPermission::userHasRoleGlobal($user, 'admin');
FamiqPermission::userHasRoleInProject($user, 'gerente_encuestas', $projectId);

FamiqPermission::userHasPermissionGlobal($user, 'ingresar');
FamiqPermission::userHasPermissionInProject($user, 'leer_encuestas', $project);
FamiqPermission::userHasPermission($user, 'leer_encuestas');
```

La lógica evalúa simultáneamente:

- Roles globales del usuario.
- Roles asignados al usuario para el proyecto solicitado.
- Permisos globales (`project_id = null`).
- Permisos asociados al proyecto consultado.

## Flujo sugerido para seeders

1. Crear proyectos (p.ej. `Encuestas`).
2. Crear permisos globales (`ingresar`) y específicos (`leer_encuestas`, ligado al proyecto de Encuestas).
3. Crear roles con su `scope` (`admin` global, `gerente_encuestas` project).
4. Asociar permisos a roles vía `Role::permissions()`.
5. Declarar qué roles están habilitados en cada proyecto usando `ProjectRole`.
6. Asignar roles a usuarios con `UserRole`, dejando `project_id` en `null` para roles globales.

### Ejemplo práctico

```php
use App\Models\Project;
use App\Models\User;
use Famiq\Permission\Models\Permission;
use Famiq\Permission\Models\ProjectRole;
use Famiq\Permission\Models\Role;
use Famiq\Permission\Models\UserRole;

$project = Project::create(['name' => 'Encuestas']);

$admin = Role::create(['name' => 'Admin', 'slug' => 'admin', 'scope' => 'global']);
$manager = Role::create(['name' => 'Gerente Encuestas', 'slug' => 'gerente_encuestas', 'scope' => 'project']);

$login = Permission::create(['name' => 'Ingresar', 'slug' => 'ingresar']);
$readSurveys = Permission::create([
    'name' => 'Leer Encuestas',
    'slug' => 'leer_encuestas',
    'project_id' => $project->id,
]);

$admin->permissions()->sync([$login->id]);
$manager->permissions()->sync([$login->id, $readSurveys->id]);

ProjectRole::create(['project_id' => $project->id, 'role_id' => $manager->id]);

$user = User::factory()->create();

UserRole::create(['user_id' => $user->id, 'role_id' => $admin->id]); // global
UserRole::create([
    'user_id' => $user->id,
    'role_id' => $manager->id,
    'project_id' => $project->id,
]);

$user->hasRoleGlobal('admin'); // true
$user->hasRoleInProject('gerente_encuestas', $project); // true

$user->canGlobal('ingresar'); // true
$user->canInProject('leer_encuestas', $project); // true
$user->canAnywhere('leer_encuestas'); // true porque tiene el permiso en Encuestas
```

## Tests y desarrollo

Este repositorio trae una suite basada en Orchestra Testbench. Para ejecutarla:

```bash
composer install
composer test
```

(En entornos sin acceso a Packagist podés copiar el package directamente dentro de tu monorepo y ejecutar las pruebas de Laravel que consuman el trait/servicio.)

## Licencia

MIT. Consulta `LICENSE.md` para más detalles.
