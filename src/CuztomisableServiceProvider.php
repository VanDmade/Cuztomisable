<?php

namespace VanDmade\Cuztomisable;

use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Router;
use VanDmade\Cuztomisable\Middleware\CheckPermission;
use VanDmade\Cuztomisable\Middleware\RequireAdmin;

class CuztomisableServiceProvider extends ServiceProvider
{

    public function boot(): void
    {
        $router = $this->app->make(Router::class);
        // Register route middleware alias
        $router->aliasMiddleware('permission', CheckPermission::class);
        $router->aliasMiddleware('require-admin', RequireAdmin::class);
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Route::prefix('api')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                Middleware\TokenFromCookie::class,
                Middleware\RequireCsrfUnlessMobile::class,
                Middleware\EnsureValidMobileAgent::class,
            ])
            ->group(__DIR__.'/../routes.php');
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->mergeConfigFrom(__DIR__.'/../config/account.php', 'cuztomisable.account');
        $this->mergeConfigFrom(__DIR__.'/../config/rate_limits.php', 'cuztomisable.rate_limits');
        $this->mergeConfigFrom(__DIR__.'/../config/locations.php', 'cuztomisable.locations');
        $this->mergeConfigFrom(__DIR__.'/../config/login.php', 'cuztomisable.login');
        $this->mergeConfigFrom(__DIR__.'/../config/respondify.php', 'cuztomisable.respondify');
        $this->mergeConfigFrom(__DIR__.'/../config/tablelify.php', 'cuztomisable.tablelify');
        $this->publishes([
            __DIR__.'/../config/account.php' => config_path('cuztomisable/account.php'),
            __DIR__.'/../config/rate_limits.php' => config_path('cuztomisable/rate_limits.php'),
            __DIR__.'/../config/locations.php' => config_path('cuztomisable/locations.php'),
            __DIR__.'/../config/login.php' => config_path('cuztomisable/login.php'),
            __DIR__.'/../config/respondify.php' => config_path('cuztomisable/respondify.php'),
            __DIR__.'/../config/tablelify.php' => config_path('cuztomisable/tablelify.php'),
            __DIR__.'/../resources/js' => resource_path('js'),
            __DIR__.'/../resources/sass' => resource_path('sass'),
            __DIR__.'/../resources/views/' => resource_path('views'),
            __DIR__.'/../resources/languages/en' => resource_path('lang/en/cuztomisable'),
            __DIR__.'/../images' => public_path('cuztomisable'),
            __DIR__.'/../database/migrations' => database_path('migrations/cuztomisable'),
        ], 'cuztomisable');
        $this->publishes([
            __DIR__.'/../config/account.php' => config_path('cuztomisable/account.php'),
            __DIR__.'/../config/rate_limits.php' => config_path('cuztomisable/rate_limits.php'),
            __DIR__.'/../config/locations.php' => config_path('cuztomisable/locations.php'),
            __DIR__.'/../config/login.php' => config_path('cuztomisable/login.php'),
            __DIR__.'/../config/respondify.php' => config_path('cuztomisable/respondify.php'),
            __DIR__.'/../config/tablelify.php' => config_path('cuztomisable/tablelify.php'),
        ], 'cuztomisable-config');
        $this->publishes([
            __DIR__.'/../resources/js' => resource_path('js'),
            __DIR__.'/../resources/sass' => resource_path('sass'),
            __DIR__.'/../resources/views/' => resource_path('views'),
            __DIR__.'/../resources/languages/en' => resource_path('lang/en/cuztomisable'),
            __DIR__.'/../images' => public_path('cuztomisable'),
        ], 'cuztomisable-assets');
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations/cuztomisable'),
        ], 'cuztomisable-migrations');
    }

}