<?php

declare(strict_types=1);

namespace Famiq\Permission\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Famiq\Permission\PermissionServiceProvider;
use Famiq\Permission\Tests\Fixtures\Project;
use Famiq\Permission\Tests\Fixtures\User;

abstract class TestCase extends Orchestra
{
    /**
     * Prepara el entorno ejecutando las migraciones de soporte.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->artisan('migrate', ['--database' => 'testbench'])->run();
    }

    /**
     * Registra el service provider del paquete para las pruebas.
     */
    protected function getPackageProviders($app): array
    {
        return [PermissionServiceProvider::class];
    }

    /**
     * Configura la base de datos in-memory y los modelos personalizados.
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('famiq-permission.user_model', User::class);
        $app['config']->set('famiq-permission.project_model', Project::class);
        $app['config']->set('famiq-permission.tables.users', 'users');
        $app['config']->set('famiq-permission.tables.projects', 'projects');
    }
}
