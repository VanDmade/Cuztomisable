<?php

use VanDmade\Cuztomisable\Controllers\SettingsController;
use VanDmade\Cuztomisable\Controllers\Authentication;
use VanDmade\Cuztomisable\Controllers\PermissionController;
use VanDmade\Cuztomisable\Controllers\RoleController;
use VanDmade\Cuztomisable\Controllers\Users\AccessController;
use VanDmade\Cuztomisable\Controllers\Users\UserController;
use VanDmade\Cuztomisable\Controllers\Users\PasswordController;
use VanDmade\Cuztomisable\Controllers\Users\IpAddressController;
use VanDmade\Cuztomisable\Controllers\FormoraController;

Route::controller(SettingsController::class)->group(function () {
    Route::get('/cuztomisable/settings', 'all');
});
Route::controller(Authentication\LoginController::class)->group(function () {
    Route::post('/login', 'login');
    Route::post('/logout', 'logout');
});
Route::controller(Authentication\MFAController::class)->group(function () {
    Route::post('/login/mfa/{token}/send', 'send');
    Route::get('/login/mfa/{token}/verify', 'verify');
    Route::post('/login/mfa/{token}', 'save');
});
Route::controller(Authentication\PasswordController::class)->group(function () {
    Route::post('/password/forgot', 'forgot');
    Route::get('/password/forgot/{token}/send', 'send');
    Route::post('/password/forgot/{token}', 'save');
    Route::get('/password/forgot/{token}/verify/{code?}', 'verify');
});
Route::controller(Authentication\RegistrationController::class)->group(function () {
    Route::get('/register/verify/{code}', 'verify');
    Route::post('/register/{code?}', 'save');
});
Route::controller(Authentication\PasswordController::class)->group(function () {
    Route::get('/lock/{user}/reset/{id}/{token}', 'lock');
});
Route::controller(UserController::class)->group(function () {
    Route::post('/refresh/token', 'refreshToken');
});
Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::controller(FormoraController::class)->group(function () {
        Route::get('/formora/{page}', 'get');
        Route::post('/formora/{page}', 'save');
    });
    Route::controller(AccessController::class)->group(function () {
        Route::get('/user/{id}/access', 'get')
            ->middleware('permission:view-user-roles-permissions|manage-user-roles-permissions');
        Route::post('/user/{id}/access', 'save')
            ->middleware('permission:manage-user-roles-permissions');
    });
    Route::controller(UserController::class)->group(function () {
        Route::get('/me', 'get');
        Route::get('/refresh', 'refresh');
        Route::middleware(['permission:view-users|manage-users'])->group(function () {
            Route::get('/list/users', 'list');
            Route::get('/users', 'table');
            Route::get('/user/{id}', 'get');
        });
        // Route for specifically altering your own account
        Route::post('/profile', 'save')->name('profile');
        Route::patch('/mfa', 'toggleMfa')->name('toggle.mfa');
        Route::middleware(['permission:manage-users'])->group(function () {
            Route::post('/user/{id?}', 'save')->name('users.update');
            Route::delete('/user/{id}', 'toggleDelete');
            Route::patch('/user/{id}/locked', 'toggleLocked');
        });
        Route::patch('/user/{id}/mfa', 'toggleMfa')
            ->middleware('permission:toggle-user-mfa');
    });
    Route::middleware(['permission:invite-users'])->group(function () {
        Route::controller(Authentication\RegistrationController::class)->group(function () {
            Route::get('/invites', 'table');
            Route::post('/invite', 'invite');
            Route::delete('/invite/{id}', 'toggleDelete');
            Route::post('/invite/{id}/send', 'send');
        });
    });
    Route::controller(PasswordController::class)->group(function () {
        Route::post('/user/change/password', 'change');
        Route::post('/user/{id}/send/password', 'send')
            ->middleware('permission:reset-user-passwords');
    });
    Route::controller(IpAddressController::class)->group(function () {
        Route::get('/ip/{id}', 'get');
        Route::get('/user/{id}/ips', 'table');
        Route::patch('/ip/{id}', 'save');
        Route::middleware(['permission:clear-user-logins'])->group(function () {
            Route::delete('/ip/{id}/forget', 'forget');
            Route::delete('/ip/{id}', 'toggleDelete');
        });
    });
    Route::controller(RoleController::class)->group(function () {
        Route::get('/list/roles', 'list');
    });
    Route::controller(PermissionController::class)->group(function () {
        Route::get('/list/permissions', 'list');
        Route::get('/list/role/{id}/permissions', 'list');
    });
    Route::middleware(['permission:manage-roles-permissions'])->group(function () {
        Route::controller(RoleController::class)->group(function () {
            Route::get('/role/{id}', 'get');
            Route::get('/roles', 'table');
            Route::post('/role/{id?}', 'save');
            Route::delete('/role/{id}', 'toggleDelete');
            Route::delete('/role/{id}/permission/{permission}', 'removePermission');
        });
        Route::controller(PermissionController::class)->group(function () {
            Route::get('/permission/{id}', 'get');
            Route::get('/permissions', 'table');
            Route::post('/permission/{id?}', 'save');
            Route::delete('/permission/{id}', 'toggleDelete');
        });
    });
});