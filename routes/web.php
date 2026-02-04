<?php

use Illuminate\Support\Facades\Route;

// Catch-all SPA (Vue / React)
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api).*$');
