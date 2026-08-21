<?php

use VanDmade\Cuztomisable\Http\Controllers\SettingsController;
use VanDmade\Cuztomisable\Http\Controllers\PermissionController;
use VanDmade\Cuztomisable\Http\Controllers\RoleController;
use VanDmade\Cuztomisable\Http\Controllers\FormController;
use VanDmade\Cuztomisable\Http\Controllers\Users\AccessController;
use VanDmade\Cuztomisable\Http\Controllers\Users\PasswordController;
use VanDmade\Cuztomisable\Http\Controllers\Users\IpAddressController;
use VanDmade\Cuztomisable\Http\Controllers\Authentication\RegistrationController;
use VanDmade\Cuztomisable\Http\Controllers\Authentication\LoginController;
use VanDmade\Cuztomisable\Http\Controllers\Users\UserController;
use VanDmade\Cuztomisable\Http\Controllers\Authentication\MFAController;
use VanDmade\Cuztomisable\Http\Controllers\Authentication\PasswordController as RegistrationPasswordController;

Route::controller(SettingsController::class)->group(function() {
    Route::get('/cuztomisable/settings', 'all');
});
Route::controller(LoginController::class)->group(function() {
    Route::post('/login', 'login');
    Route::post('/logout', 'logout');
});
Route::controller(MFAController::class)->group(function() {
    Route::post('/login/mfa/{token}/send', 'send')
        ->middleware('throttler:mfa.send,ip,params=token');
    Route::get('/login/mfa/{token}/verify', 'verify')
        ->middleware('throttler:mfa.verify,ip,params=token');
    Route::post('/login/mfa/{token}', 'save')
        ->middleware('throttler:mfa.save,ip,params=token');
});
Route::controller(RegistrationPasswordController::class)->group(function() {
    Route::post('/password/forgot', 'forgot')
        ->middleware('throttler:passwords.forgot,ip,request=username');
    Route::get('/password/forgot/{token}/send', 'send')
        ->middleware('throttler:passwords.send,ip,params=token');
    Route::post('/password/forgot/{token}', 'save')
        ->middleware('throttler:passwords.reset,ip,params=token');
    Route::get('/password/forgot/{token}/verify/{code?}', 'verify')
        ->middleware('throttler:passwords.verify,ip,params=token');
});
Route::controller(RegistrationController::class)->group(function() {
    Route::get('/register/verify/{code}', 'verify')
        ->middleware('throttler:registration.verify,ip,params=code');
    Route::post('/register/{code?}', 'save')
        ->middleware('throttler:registration.save,ip,request=email,request=phone');
});
Route::controller(RegistrationPasswordController::class)->group(function() {
    Route::get('/lock/{user}/reset/{id}/{token}', 'lock');
});
Route::controller(UserController::class)->group(function() {
    Route::post('/refresh/token', 'refreshToken')
        ->middleware('throttler:users.refresh_token,ip');
});
Route::group(['middleware' => ['auth:sanctum']], function() {
    Route::controller(SettingsController::class)->group(function() {
        Route::patch('/settings/timezone', 'updateTimezone');
    });
    Route::controller(FormController::class)->group(function() {
        Route::get('/form/{page}', 'get')
            ->middleware('throttler:form.get,ip,actor,params=page');
        Route::post('/form/{page}', 'save')
            ->middleware('throttler:form.save,ip,actor,params=page,request=current');
    });
    Route::controller(AccessController::class)->group(function() {
        Route::get('/user/{id}/access', 'get')
            ->middleware('permission:view-user-roles-permissions|manage-user-roles-permissions');
        Route::post('/user/{id}/access', 'save')
            ->middleware(['permission:manage-user-roles-permissions', 'throttler:access.save,actor,params=id']);
    });
    Route::controller(UserController::class)->group(function() {
        Route::get('/me', 'get');
        Route::get('/refresh', 'refresh')
            ->middleware('throttler:users.refresh,ip,actor');
        Route::middleware(['permission:view-users|manage-users'])->group(function() {
            Route::get('/list/users', 'list');
            Route::get('/users', 'table');
            Route::get('/user/{id}', 'get');
        });
        // Route for specifically altering your own account
        Route::post('/profile', 'save')
            ->middleware('throttler:users.save,ip,actor,params=id')
            ->name('profile');
        Route::patch('/mfa', 'toggleMfa')
            ->middleware('throttler:users.toggle_mfa,actor,params=id')
            ->name('toggle.mfa');
        Route::middleware(['permission:manage-users'])->group(function() {
            Route::post('/user/{id?}', 'save')
                ->middleware('throttler:users.save,ip,actor,params=id')
                ->name('users.update');
            Route::delete('/user/{id}', 'toggleDelete')
                ->middleware('throttler:users.toggle_delete,actor,params=id');
            Route::patch('/user/{id}/locked', 'toggleLocked')
                ->middleware('throttler:users.toggle_locked,actor,params=id');
        });
        Route::patch('/user/{id}/mfa', 'toggleMfa')
            ->middleware(['permission:toggle-user-mfa', 'throttler:users.toggle_mfa,actor,params=id']);
    });
    Route::middleware(['permission:invite-users'])->group(function() {
        Route::controller(RegistrationController::class)->group(function() {
            Route::get('/invites', 'table');
            Route::post('/invite', 'invite')
                ->middleware('throttler:registration.invite,ip,request=email,request=phone');
            // withTrashed() - toggleDelete needs to find already-deleted registrations too, to restore them
            Route::delete('/invite/{registration}', 'toggleDelete')->withTrashed();
            Route::post('/invite/{registration}/send', 'send')
                ->middleware('throttler:registration.send,ip,params=registration');
        });
    });
    Route::controller(PasswordController::class)->group(function() {
        Route::post('/user/change/password', 'change')
            ->middleware('throttler:user_passwords.change,ip,actor');
        Route::post('/user/{id}/send/password', 'send')
            ->middleware(['permission:reset-user-passwords', 'throttler:user_passwords.send,actor,params=id']);
    });
    Route::controller(IpAddressController::class)->group(function() {
        Route::get('/ip/{id}', 'get');
        Route::get('/user/{id}/ips', 'table');
        Route::patch('/ip/{id}', 'save')
            ->middleware('throttler:ip_addresses.save,actor,params=id');
        Route::middleware(['permission:clear-user-logins'])->group(function() {
            Route::delete('/ip/{id}/forget', 'forget')
                ->middleware('throttler:ip_addresses.forget,actor,params=id');
            Route::delete('/ip/{id}', 'toggleDelete')
                ->middleware('throttler:ip_addresses.toggle_delete,actor,params=id');
        });
    });
    Route::controller(RoleController::class)->group(function() {
        Route::get('/list/roles', 'list');
    });
    Route::controller(PermissionController::class)->group(function() {
        Route::get('/list/permissions', 'list');
        Route::get('/list/role/{id}/permissions', 'list');
    });
    Route::middleware(['permission:manage-roles-permissions'])->group(function() {
        Route::controller(RoleController::class)->group(function() {
            Route::get('/role/{id}', 'get');
            Route::get('/roles', 'table');
            Route::post('/role/{id?}', 'save')
                ->middleware('throttler:roles.save,actor,params=id');
            Route::delete('/role/{id}', 'toggleDelete')
                ->middleware('throttler:roles.toggle_delete,actor,params=id');
            Route::delete('/role/{id}/permission/{permission}', 'removePermission')
                ->middleware('throttler:roles.remove_permission,actor,params=id,params=permission');
        });
        Route::controller(PermissionController::class)->group(function() {
            Route::get('/permission/{id}', 'get');
            Route::get('/permissions', 'table');
            Route::post('/permission/{id?}', 'save')
                ->middleware('throttler:permissions.save,actor,params=id');
            Route::delete('/permission/{id}', 'toggleDelete')
                ->middleware('throttler:permissions.toggle_delete,actor,params=id');
        });
    });
});
