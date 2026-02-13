<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\StudentController;

// Route::any('register-debug', function (Request $request) {
//     return response()->json([
//         'method'     => $request->method(),
//         'received'   => $request->all(),
//         'raw_body'   => $request->getContent(),
//         'client_ip'  => $request->ip(),
//         'headers'    => $request->headers->all(),
//     ]);
// });

//test
Route::post('test', function () {
    return response()->json(['ok' => true]);
});


Route::get('ping', fn () => response('pong'));


Route::post('register', [\App\Http\Controllers\Api\RegistrationController::class, 'register']);

Route::post('students/login', [\App\Http\Controllers\Api\StudentAuthController::class, 'requestOtp']);
Route::post('students/verify-otp', [\App\Http\Controllers\Api\StudentAuthController::class, 'verifyOtp']);
Route::post('students/me-by-token', [\App\Http\Controllers\Api\StudentAuthController::class, 'meByToken']);

Route::match(['post','get'], 'admin/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);

//sanctum
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::match(['get','post'], 'logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
    Route::get('me', [\App\Http\Controllers\Api\AuthController::class, 'me']);

    Route::apiResource('users', \App\Http\Controllers\Api\UserController::class);
    Route::post('users/{id}/assign-role', [\App\Http\Controllers\Api\UserController::class, 'assignRole']);
    Route::post('users/{id}/remove-role', [\App\Http\Controllers\Api\UserController::class, 'removeRole']);

    Route::get('roles', [\App\Http\Controllers\Api\RoleController::class, 'index']);

    Route::apiResource('students', \App\Http\Controllers\Api\StudentController::class);
    Route::get('students/{id}/financials', [StudentController::class, 'financials']);
    Route::post('students/{id}/payment-link', [StudentController::class, 'createPaymentLink']);
    Route::apiResource('institutions', \App\Http\Controllers\Api\InstitutionController::class);
    Route::get('institutions/{id}/annexes', [\App\Http\Controllers\Api\InstitutionController::class, 'annexes']);
    Route::apiResource('annexes', \App\Http\Controllers\Api\AnnexeController::class);
});
