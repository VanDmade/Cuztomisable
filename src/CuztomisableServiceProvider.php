<?php

namespace VanDmade\Cuztomisable;

use Illuminate\Support\ServiceProvider;

class CuztomisableServiceProvider extends ServiceProvider
{

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->publishes([
            __DIR__.'/../config/account.php' => config_path('cuztomisable/account.php'),
            __DIR__.'/../config/locations.php' => config_path('cuztomisable/locations.php'),
            __DIR__.'/../config/login.php' => config_path('cuztomisable/login.php'),
            __DIR__.'/../config/respondify.php' => config_path('cuztomisable/respondify.php'),
            __DIR__.'/../config/tablelify.php' => config_path('cuztomisable/tablelify.php'),
            __DIR__.'/../resources/js' => resource_path('js'),
            __DIR__.'/../resources/sass' => resource_path('sass'),
            __DIR__.'/../resources/views/' => resource_path('views'),
            __DIR__.'/../resources/languages/en' => resource_path('lang/en/cuztomisable'),
            __DIR__.'/../database/migrations' => database_path('migrations/cuztomisable'),
        ]);
    }

}