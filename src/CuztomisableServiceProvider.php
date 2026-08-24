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
use VanDmade\Cuztomisable\Middleware\CheckPermission;
use VanDmade\Cuztomisable\Middleware\HandleInertiaRequests;
use VanDmade\Cuztomisable\Middleware\RequireAdmin;
use VanDmade\Cuztomisable\Middleware\RequireCurrentTerms;
use VanDmade\Cuztomisable\Middleware\Throttler;
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

        Route::middleware([
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            Middleware\TokenFromCookie::class,
            Middleware\RequireCsrfUnlessMobile::class,
            Middleware\EnsureValidMobileAgent::class,
            HandleInertiaRequests::class,
        ])->group(function () {
            Route::get('/', fn () => Inertia::render('authentication/Login'))->name('page.login');
            Route::get('/login', fn () => Inertia::render('authentication/Login'))->name('page.login.alias');
            Route::get('/registration/{code?}', fn () => Inertia::render('authentication/Registration'))->name('page.registration');
            Route::get('/forgot', fn () => Inertia::render('authentication/Forgot'))->name('page.forgot');
            Route::get('/reset/{token}', fn () => Inertia::render('authentication/Reset'))->name('page.reset');
            Route::get('/mfa/{token}', fn () => Inertia::render('authentication/MFA'))->name('page.mfa');
            Route::get('/message', fn () => Inertia::render('Message'))->name('page.message');
            Route::get('/portal', fn () => Inertia::render('Portal'))->name('page.portal');
            Route::get('/profile', fn () => Inertia::render('users/Form'))->name('page.profile');
            Route::get('/user/{id}', fn () => Inertia::render('users/Form'))->name('page.user.form');
            Route::get('/users', fn () => Inertia::render('users/Table'))->name('page.users');
            Route::get('/invites', fn () => Inertia::render('users/invites/Table'))->name('page.invites');
            Route::get('/roles', fn () => Inertia::render('roles/Table'))->name('page.roles');
            Route::get('/permissions', fn () => Inertia::render('permissions/Table'))->name('page.permissions');
        });
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->mergeConfigFrom(__DIR__.'/../config/cuztomisable.php', 'cuztomisable');
        // email.php/text.php are source-organization only, not separately published - they merge
        // into the same nested paths the single published cuztomisable.php already exposes.
        $this->mergeConfigFrom(__DIR__.'/../config/email.php', 'cuztomisable.notifications.emails');
        $this->mergeConfigFrom(__DIR__.'/../config/text.php', 'cuztomisable.notifications.texts');
        $this->mergeConfigFrom(__DIR__.'/../config/rate_limits.php', 'cuztomisable.rate_limits');
        $this->mergeConfigFrom(__DIR__.'/../config/passwords.php', 'cuztomisable.account.passwords');
        $this->app->bind(SmsProviderInterface::class, config('cuztomisable.sms_provider', AwsSnsSmsProvider::class));
        $this->publishes([
            __DIR__.'/../config/cuztomisable.php' => config_path('cuztomisable.php'),
            __DIR__.'/../resources/js' => resource_path('js'),
            __DIR__.'/../resources/sass' => resource_path('sass'),
            __DIR__.'/../resources/views/' => resource_path('views'),
            __DIR__.'/../resources/views/emails' => resource_path('views/vendor/cuztomisable'),
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
        // Override just the emails, without pulling in JS/sass/config/etc - publishes into the
        // vendor-namespaced path loadViewsFrom() checks first, so unpublished views keep using
        // the package's bundled default and this only ever needs to contain what's customized.
        $this->publishes([
            __DIR__.'/../resources/views/emails' => resource_path('views/vendor/cuztomisable'),
        ], 'cuztomisable-views');
    }

}