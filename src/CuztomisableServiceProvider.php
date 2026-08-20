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
use VanDmade\Cuztomisable\Sms\AwsSnsSmsProvider;
use VanDmade\Cuztomisable\Sms\SmsProviderInterface;

class CuztomisableServiceProvider extends ServiceProvider
{

    public function boot(): void
    {
        $router = $this->app->make(Router::class);
        // Register route middleware alias
        $router->aliasMiddleware('permission', CheckPermission::class);
        $router->aliasMiddleware('require-admin', RequireAdmin::class);
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../database/seeders' => database_path('seeders'),
            ], 'cuztomisable-seeders');
        }
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
        $this->mergeConfigFrom(__DIR__.'/../config/cuztomisable.php', 'cuztomisable');
        $this->app->bind(SmsProviderInterface::class, config('cuztomisable.sms_provider', AwsSnsSmsProvider::class));
        $this->publishes([
            __DIR__.'/../config/cuztomisable.php' => config_path('cuztomisable.php'),
            __DIR__.'/../resources/js' => resource_path('js'),
            __DIR__.'/../resources/sass' => resource_path('sass'),
            __DIR__.'/../resources/views/' => resource_path('views'),
            __DIR__.'/../resources/languages/en' => resource_path('lang/en/cuztomisable'),
            __DIR__.'/../images' => public_path('cuztomisable'),
            __DIR__.'/../database/migrations' => database_path('migrations/cuztomisable'),
        ], 'cuztomisable');
        $this->publishes([
            __DIR__.'/../config/cuztomisable.php' => config_path('cuztomisable.php'),
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