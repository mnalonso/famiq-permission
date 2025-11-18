<?php

declare(strict_types=1);

namespace Famiq\Permission;

use Illuminate\Support\ServiceProvider;
use Famiq\Permission\Services\PermissionService;

/**
 * Service provider responsible for bootstrapping the package resources.
 */
class PermissionServiceProvider extends ServiceProvider
{
    /**
     * Registra la configuración y el singleton del PermissionService en el contenedor.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/famiq-permission.php', 'famiq-permission');

        $this->app->singleton(PermissionService::class, function (): PermissionService {
            return new PermissionService();
        });
    }

    /**
     * Publica los recursos y carga las migraciones del paquete.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/famiq-permission.php' => config_path('famiq-permission.php'),
        ], 'famiq-permission-config');

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'famiq-permission-migrations');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
