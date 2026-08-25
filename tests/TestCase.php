<?php

namespace VanDmade\Cuztomisable\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\SanctumServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use VanDmade\Cuztomisable\CuztomisableServiceProvider;
use VanDmade\Cuztomisable\Models\Users\User;

abstract class TestCase extends Orchestra
{

    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            CuztomisableServiceProvider::class,
            SanctumServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('queue.default', 'sync');
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('auth.providers.users.model', User::class);
        // The auth:sanctum middleware (routes/api.php) resolves this guard by config alone -
        // Sanctum's own provider registers the driver, but the guard entry itself has to exist.
        $app['config']->set('auth.guards.sanctum', ['driver' => 'sanctum', 'provider' => 'users']);
    }

    protected function defineDatabaseMigrations(): void
    {
        // Cuztomisable owns and creates its own users table
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

}
