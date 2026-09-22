<?php

use Illuminate\Support\Facades\Route;
use WebKampus\SSOClient\Http\Controllers\SSOController;

$prefix = config('sso.route_prefix', 'sso');
$middleware = config('sso.route_middleware', ['web']);

Route::middleware($middleware)->prefix($prefix)->group(function () {
    Route::get('/redirect', [SSOController::class, 'redirect'])->name('sso.redirect');
    Route::get('/callback', [SSOController::class, 'callback'])->name('sso.callback');
    Route::post('/logout', [SSOController::class, 'logout'])->name('sso.logout');
});
