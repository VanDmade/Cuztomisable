<?php

use VanDmade\Cuztomisable\Controllers\SettingsController;
use VanDmade\Cuztomisable\Controllers\Authentication;
use VanDmade\Cuztomisable\Controllers\PermissionController;
use VanDmade\Cuztomisable\Controllers\RoleController;
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
    Route::post('/register/{code?}', 'save');
});
Route::controller(Authentication\PasswordController::class)->group(function () {
    Route::get('/lock/{user}/reset/{id}/{token}', 'lock');
});
Route::controller(FormoraController::class)->group(function () {
    Route::get('/formora/{page}', 'get');
    Route::post('/formora/{page}', 'save');
});
Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::controller(UserController::class)->group(function () {
        Route::get('/me', 'get');
        Route::get('/list/users', 'list');
        Route::get('/users', 'table');
        Route::get('/user/{id}', 'get');
        Route::post('/user/{id?}', 'save');
        Route::delete('/user/{id}', 'toggleDelete');
        Route::patch('/user/{id}/locked', 'toggleLocked');
        Route::patch('/user/{id}/mfa', 'toggleMfa');
        Route::get('/refresh', 'refresh');
    });
    Route::controller(PasswordController::class)->group(function () {
        Route::post('/user/change/password', 'change');
        Route::post('/user/{id}/send/password', 'send');
    });
    Route::controller(IpAddressController::class)->group(function () {
        Route::get('/ip/{id}', 'get');
        Route::get('/user/{id}/ips', 'table');
        Route::delete('/ip/{id}/forget', 'forget');
        Route::delete('/ip/{id}', 'toggleDelete');
    });
    Route::controller(RoleController::class)->group(function () {
        Route::get('/role/{id}', 'get');
        Route::get('/roles', 'table');
        Route::post('/role/{id?}', 'save');
        Route::get('/list/roles', 'list');
        Route::delete('/role/{id}', 'toggleDelete');
        Route::delete('/role/{id}/permission/{permission}', 'removePermission');
    });
    Route::controller(PermissionController::class)->group(function () {
        Route::get('/permission/{id}', 'get');
        Route::get('/permissions', 'table');
        Route::post('/permission/{id?}', 'save');
        Route::get('/list/permissions', 'list');
        Route::get('/list/role/{id}/permissions', 'list');
        Route::delete('/permission/{id}', 'toggleDelete');
    });
});