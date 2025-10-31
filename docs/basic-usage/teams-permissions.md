---
title: Projects permissions
weight: 5
---

When enabled, projects permissions offers you flexible control for a variety of scenarios. The idea behind projects permissions is inspired by the default permission implementation of [Laratrust](https://laratrust.santigarcor.me/).

## Enabling Projects Permissions Feature

NOTE: These configuration changes must be made **before** performing the migration when first installing the package.

If you have already run the migration and want to upgrade your implementation, you can run the artisan console command `php artisan permission:setup-projects`, to create a new migration file named [xxxx_xx_xx_xx_add_projects_fields.php](https://github.com/spatie/laravel-permission/blob/main/database/migrations/add_projects_fields.php.stub) and then run `php artisan migrate` to upgrade your database tables.

Projects permissions can be enabled in the permission config file:

```php
// config/permission.php
'projects' => true,
```

Also, if you want to use a custom foreign key for projects you set it in the permission config file:
```php
// config/permission.php
'project_foreign_key' => 'custom_project_id',
```

## Working with Projects Permissions

After implementing a solution for selecting a project on the authentication process 
(for example, setting the `project_id` of the currently selected project on the **session**: `session(['project_id' => $project->project_id]);` ), 
we can set global `project_id` from anywhere, but works better if you create a `Middleware`. 

Example Project Middleware:

```php
namespace App\Http\Middleware;

class ProjectsPermission
{
    
    public function handle($request, \Closure $next){
        if(!empty(auth()->user())){
            // session value set on login
            setPermissionsProjectId(session('project_id'));
        }
        // other custom ways to get project_id
        /*if(!empty(auth('api')->user())){
            // `getProjectIdFromToken()` example of custom method for getting the set project_id 
            setPermissionsProjectId(auth('api')->user()->getProjectIdFromToken());
        }*/
        
        return $next($request);
    }
}
```

**YOU MUST ALSO** set [the `$middlewarePriority` array](https://laravel.com/docs/master/middleware#sorting-middleware) in `app/Http/Kernel.php` to include your custom middleware before the `SubstituteBindings` middleware, else you may get *404 Not Found* responses when a *403 Not Authorized* response might be expected.

For example, in Laravel 11.27+ you can add something similiar to the `boot` method of your `AppServiceProvider`.

```php
use App\Http\Middleware\YourCustomMiddlewareClass;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        /** @var Kernel $kernel */
        $kernel = app()->make(Kernel::class);

        $kernel->addToMiddlewarePriorityBefore(
            YourCustomMiddlewareClass::class,
            SubstituteBindings::class,
        );
    }
}
```
### Using LiveWire? 

You may need to register your project middleware as Persisted in Livewire. See [Livewire docs: Configuring Persistent Middleware](https://livewire.laravel.com/docs/security#configuring-persistent-middleware)

## Roles Creating

When creating a role you can pass the `project_id` as an optional parameter
 
```php
// with null project_id it creates a global role; global roles can be assigned to any project and they are unique
Role::create(['name' => 'writer', 'project_id' => null]);

// creates a role with project_id = 1; project roles can have the same name on different projects
Role::create(['name' => 'reader', 'project_id' => 1]);

// creating a role without project_id makes the role take the default global project_id
Role::create(['name' => 'reviewer']);
```

## Roles/Permissions Assignment and Removal

The role/permission assignment and removal for projects are the same as without projects, but they take the global `project_id` which is set on login.

## Changing The Active Project ID

While your middleware will set a user's `project_id` upon login, you may later need to set it to another project for various reasons. The two most common reasons are these:

### Switching Projects After Login
If your application allows the user to switch between various projects which they belong to, you can activate the roles/permissions for that project by calling `setPermissionsProjectId($new_project_id)` and unsetting relations as described below.

### Administrating Project Details
You may have created a User-Manager page where you can view the roles/permissions of users on certain projects. For managing that user in each project they belong to, you must also use `setPermissionsProjectId($new_project_id)` to cause lookups to relate to that new project, and unset prior relations as described below.

### Querying Roles/Permissions for Other Projects
Whenever you switch the active `project_id` using `setPermissionsProjectId()`, you need to `unset` the user's/model's `roles` and `permissions` relations before querying what roles/permissions that user has (`$user->roles`, etc) and before calling any authorization functions (`can()`, `hasPermissionTo()`, `hasRole()`, etc).

Example:
```php
// set active global project_id
setPermissionsProjectId($new_project_id);

// $user = Auth::user();

// unset cached model relations so new project relations will get reloaded
$user->unsetRelation('roles')->unsetRelation('permissions');

// Now you can check:
$roles = $user->roles;
$hasRole = $user->hasRole('my_role');
$user->hasPermissionTo('foo');
$user->can('bar');
// etc
```

## Defining a Super-Admin on Projects

Global roles can be assigned to different projects, and `project_id` (which is the primary key of the relationships) is always required. 

If you want a "Super Admin" global role for a user, when you create a new project you must assign it to your user. Example:

```php
namespace App\Models;

class YourProjectModel extends \Illuminate\Database\Eloquent\Model
{
    // ...
    public static function boot()
    {
        parent::boot();

        // here assign this project to a global user with global default role
        self::created(function ($model) {
           // temporary: get session project_id for restore at end
           $session_project_id = getPermissionsProjectId();
           // set actual new project_id to package instance
           setPermissionsProjectId($model);
           // get the admin user and assign roles/permissions on new project model
           User::find('your_user_id')->assignRole('Super Admin');
           // restore session project_id to package instance using temporary value stored above
           setPermissionsProjectId($session_project_id);
        });
    }
    // ...
}
```
