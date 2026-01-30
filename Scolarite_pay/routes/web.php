<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RegistrationController;

Route::prefix('api')->group(function () {
    // register pour institution...utilisé une fois
    Route::post('/register', [RegistrationController::class, 'register']);

    Route::post('/login', [AuthController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

// Route catch-all pour Vue Router (SPA)
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
