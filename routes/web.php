<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn() => Inertia::render('authentication/Login'))->name('page.login');
Route::get('/login', fn() => Inertia::render('authentication/Login'))->name('page.login.alias');
Route::get('/registration/{code?}', function (?string $code = null) {
    if (!$code && config('cuztomisable.account.registration.disabled.web', false)) {
        return redirect()->route('page.login');
    }
    return Inertia::render('authentication/Registration');
})->name('page.registration');
Route::get('/forgot', fn() => Inertia::render('authentication/Forgot'))->name('page.forgot');
Route::get('/reset/{token}', fn() => Inertia::render('authentication/Reset'))->name('page.reset');
Route::get('/mfa/{token}', fn() => Inertia::render('authentication/MFA'))->name('page.mfa');
Route::get('/message', fn() => Inertia::render('Message'))->name('page.message');
Route::get('/portal', fn() => Inertia::render('Portal'))->name('page.portal');
Route::get('/profile', fn() => Inertia::render('users/Form'))->name('page.profile');
Route::get('/user/{id}', fn() => Inertia::render('users/Form'))->name('page.user.form');
Route::get('/users', fn() => Inertia::render('users/Table'))->name('page.users');
Route::get('/invites', fn() => Inertia::render('users/invites/Table'))->name('page.invites');
Route::get('/roles', fn() => Inertia::render('roles/Table'))->name('page.roles');
Route::get('/permissions', fn() => Inertia::render('permissions/Table'))->name('page.permissions');
