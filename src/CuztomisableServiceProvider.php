<?php

namespace VanDmade\Cuztomisable;

use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Router;
use Inertia\Inertia;
use VanDmade\Cuztomisable\Console\Commands\InstallCommand;
use VanDmade\Cuztomisable\Middleware\CheckPermission;
use VanDmade\Cuztomisable\Middleware\RequireAdmin;
use VanDmade\Cuztomisable\Middleware\RequireCurrentTerms;
use VanDmade\Cuztomisable\Middleware\Throttler;
use VanDmade\Cuztomisable\Models\Users\User;
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
        $router->aliasMiddleware('throttler', Throttler::class);
        $router->aliasMiddleware('require-current-terms', RequireCurrentTerms::class);
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views/emails', 'cuztomisable');
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
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
            ->group(__DIR__.'/../routes/api.php');

        // Only if the host installed inertiajs/inertia-laravel themselves - no middleware class
        // of our own, just Inertia's own render() call under the same web-safe middleware the
        // API routes already use.
        if (class_exists(Inertia::class)) {
            Inertia::setRootView('index');
            Route::middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
            ])->group(__DIR__.'/../routes/web.php');
        }
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        if (!env('AUTH_MODEL')) {
            config(['auth.providers.users.model' => User::class]);
        }
        $this->mergeConfigFrom(__DIR__.'/../config/cuztomisable.php', 'cuztomisable');
        // Merges the config files into the main config file
        $this->mergeConfigFrom(__DIR__.'/../config/email.php', 'cuztomisable.notifications.emails');
        $this->mergeConfigFrom(__DIR__.'/../config/text.php', 'cuztomisable.notifications.texts');
        $this->mergeConfigFrom(__DIR__.'/../config/rate_limits.php', 'cuztomisable.rate_limits');
        $this->mergeConfigFrom(__DIR__.'/../config/passwords.php', 'cuztomisable.account.passwords');
        $this->app->bind(SmsProviderInterface::class, config('cuztomisable.sms_provider', AwsSnsSmsProvider::class));
        // Separates the resources into sections to allow for ease of re-publishing and organization.
        $framework = [
            __DIR__.'/../resources/js/bootstrap.js' => resource_path('js/bootstrap.js'),
            __DIR__.'/../resources/js/cuztomisable.js' => resource_path('js/cuztomisable.js'),
            __DIR__.'/../resources/js/store.js' => resource_path('js/store.js'),
            __DIR__.'/../resources/js/components' => resource_path('js/components'),
            __DIR__.'/../resources/js/queues' => resource_path('js/queues'),
            __DIR__.'/../resources/js/routers' => resource_path('js/routers'),
            __DIR__.'/../resources/js/utils' => resource_path('js/utils'),
            __DIR__.'/../resources/sass' => resource_path('sass'),
            __DIR__.'/../resources/languages/en' => resource_path('lang/en/cuztomisable'),
        ];
        $pages = [
            __DIR__.'/../resources/js/views' => resource_path('js/views'),
            __DIR__.'/../resources/views/index.blade.php' => resource_path('views/index.blade.php'),
        ];
        $branding = [
            __DIR__.'/../images' => public_path('cuztomisable'),
        ];
        $this->publishes([
            __DIR__.'/../config/cuztomisable.php' => config_path('cuztomisable.php'),
            ...$framework,
            ...$pages,
            ...$branding,
            __DIR__.'/../database/migrations' => database_path('migrations/cuztomisable'),
        ], 'cuztomisable');
        $this->publishes([
            __DIR__.'/../config/cuztomisable.php' => config_path('cuztomisable.php'),
        ], 'cuztomisable-config');
        $this->publishes([
            ...$framework,
            ...$pages,
            ...$branding,
        ], 'cuztomisable-assets');
        $this->publishes($framework, 'cuztomisable-framework');
        $this->publishes($pages, 'cuztomisable-pages');
        $this->publishes($branding, 'cuztomisable-branding');
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations/cuztomisable'),
        ], 'cuztomisable-migrations');
        $this->publishes([
            __DIR__.'/../resources/views/emails' => resource_path('views/vendor/cuztomisable'),
        ], 'cuztomisable-emails');
    }

}