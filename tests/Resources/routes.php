<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::withoutMiddleware([
    ShareErrorsFromSession::class,
    StartSession::class,
    VerifyCsrfToken::class,
    PreventRequestForgery::class,
])
    ->group(function () {
        Route::get('prezet/search', function () {})->name('prezet.search');

        Route::get('prezet/img/{path}', function () {})
            ->name('prezet.image')
            ->where('path', '.*');

        Route::get('/prezet/ogimage/{slug}', function () {})
            ->name('prezet.ogimage')
            ->where('slug', '.*');

        Route::get('prezet', function () {})
            ->name('prezet.index');

        Route::get('prezet/{slug}', function () {})
            ->name('prezet.show')
            ->where('slug', '.*'); // https://laravel.com/docs/11.x/routing#parameters-encoded-forward-slashes
    });
