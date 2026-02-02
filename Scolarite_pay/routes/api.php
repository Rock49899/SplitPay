<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
| Debug: accepte toute méthode et renvoie méthode, headers et payload
*/
Route::any('register-debug', function (Request $request) {
    return response()->json([
        'method'     => $request->method(),
        'received'   => $request->all(),
        'raw_body'   => $request->getContent(),
        'client_ip'  => $request->ip(),
        'headers'    => $request->headers->all(),
    ], 200);
});

// endpoint simple pour vérifier le reachability via navigateur/curl
Route::get('ping', function () {
    return response('pong', 200)
        ->header('Content-Type', 'text/plain');
});

Route::post('register', 'App\Http\Controllers\Api\RegistrationController@register');

// Auth users (non-students)
Route::post('admin/login', 'App\Http\Controllers\Api\AuthController@login');
Route::middleware('auth:sanctum')->group(function () {
    Route::post('admin/logout', 'App\Http\Controllers\Api\AuthController@logout');
    Route::get('admin/me', 'App\Http\Controllers\Api\AuthController@me');
});

// OTP login flow for students
Route::post('students/login', 'App\Http\Controllers\Api\StudentAuthController@requestOtp');       // envoie OTP par email
Route::post('students/verify-otp', 'App\Http\Controllers\Api\StudentAuthController@verifyOtp');  // vérifie OTP et retourne token
Route::post('students/me-by-token', 'App\Http\Controllers\Api\StudentAuthController@meByToken');  // optionnel

Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    // Users CRUD
    Route::apiResource('users', 'App\Http\Controllers\Api\UserController');

    // Assign / remove role for a user (per annexe)
    Route::post('users/{id}/assign-role', 'App\Http\Controllers\Api\UserController@assignRole');
    Route::post('users/{id}/remove-role', 'App\Http\Controllers\Api\UserController@removeRole');

    // List roles (UI pour assignation)
    Route::get('roles', 'App\Http\Controllers\Api\RoleController@index');
});