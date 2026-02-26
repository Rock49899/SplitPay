<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AnnexeController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PaymentLinkController;

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
    Route::match(['put','patch','post'], 'me', [UserController::class, 'updateMe']);

    // Users management
    Route::get('users', [UserController::class, 'index'])->middleware('permission:user.view');
    Route::post('users', [UserController::class, 'store'])->middleware('permission:user.create');
    Route::get('users/{id}', [UserController::class, 'show'])->middleware('permission:user.view');
    Route::match(['put', 'patch'], 'users/{id}', [UserController::class, 'update'])->middleware('permission:user.edit');
    Route::delete('users/{id}', [UserController::class, 'destroy'])->middleware('permission:user.delete');
    Route::post('users/{id}/assign-role', [UserController::class, 'assignRole'])->middleware('permission:user.manage_roles');
    Route::post('users/{id}/remove-role', [UserController::class, 'removeRole'])->middleware('permission:user.manage_roles');

    Route::get('roles', [\App\Http\Controllers\Api\RoleController::class, 'index']);

    // Students management
    Route::get('students', [StudentController::class, 'index'])->middleware('permission:student.view');
    Route::post('students', [StudentController::class, 'store'])->middleware('permission:student.create');
    Route::get('students/{id}', [StudentController::class, 'show'])->middleware('permission:student.view');
    Route::match(['put', 'patch'], 'students/{id}', [StudentController::class, 'update'])->middleware('permission:student.edit');
    Route::delete('students/{id}', [StudentController::class, 'destroy'])->middleware('permission:student.delete');
    Route::get('students/{id}/financials', [StudentController::class, 'financials'])->middleware('permission:student.view');
    Route::post('students/{id}/payment-link', [StudentController::class, 'createPaymentLink'])->middleware('permission:link.create');

    // Institutions management
    Route::get('institutions', [\App\Http\Controllers\Api\InstitutionController::class, 'index'])->middleware('permission:institution.view');
    Route::get('institutions/{id}', [\App\Http\Controllers\Api\InstitutionController::class, 'show'])->middleware('permission:institution.view');
    Route::match(['put', 'patch'], 'institutions/{id}', [\App\Http\Controllers\Api\InstitutionController::class, 'update'])->middleware('permission:institution.edit');
    Route::get('institutions/{id}/annexes', [\App\Http\Controllers\Api\InstitutionController::class, 'annexes'])->middleware('permission:annexe.view');

    // Annexes management
    Route::get('annexes', [\App\Http\Controllers\Api\AnnexeController::class, 'index'])->middleware('permission:annexe.view');
    Route::post('annexes', [\App\Http\Controllers\Api\AnnexeController::class, 'store'])->middleware('permission:annexe.create');
    Route::get('annexes/{id}', [\App\Http\Controllers\Api\AnnexeController::class, 'show'])->middleware('permission:annexe.view');
    Route::match(['put', 'patch'], 'annexes/{id}', [\App\Http\Controllers\Api\AnnexeController::class, 'update'])->middleware('permission:annexe.edit');
    Route::delete('annexes/{id}', [\App\Http\Controllers\Api\AnnexeController::class, 'destroy'])->middleware('permission:annexe.delete');

    // Study Levels (Niveaux d'études)
    Route::get('study-levels', [\App\Http\Controllers\Api\StudyLevelController::class, 'index'])->middleware('permission:student.view');
    Route::post('study-levels', [\App\Http\Controllers\Api\StudyLevelController::class, 'store'])->middleware('permission:student.create');
    Route::get('study-levels/{id}', [\App\Http\Controllers\Api\StudyLevelController::class, 'show'])->middleware('permission:student.view');
    Route::match(['put', 'patch'], 'study-levels/{id}', [\App\Http\Controllers\Api\StudyLevelController::class, 'update'])->middleware('permission:student.edit');
    Route::delete('study-levels/{id}', [\App\Http\Controllers\Api\StudyLevelController::class, 'destroy'])->middleware('permission:student.delete');

    // Specializations (Filières)
    Route::get('specializations', [\App\Http\Controllers\Api\SpecializationController::class, 'index'])->middleware('permission:student.view');
    Route::post('specializations', [\App\Http\Controllers\Api\SpecializationController::class, 'store'])->middleware('permission:student.create');
    Route::get('specializations/{id}', [\App\Http\Controllers\Api\SpecializationController::class, 'show'])->middleware('permission:student.view');
    Route::match(['put', 'patch'], 'specializations/{id}', [\App\Http\Controllers\Api\SpecializationController::class, 'update'])->middleware('permission:student.edit');
    Route::delete('specializations/{id}', [\App\Http\Controllers\Api\SpecializationController::class, 'destroy'])->middleware('permission:student.delete');

    // Classes
    Route::get('classes', [\App\Http\Controllers\Api\StudentClassController::class, 'index'])->middleware('permission:student.view');
    Route::post('classes', [\App\Http\Controllers\Api\StudentClassController::class, 'store'])->middleware('permission:student.create');
    Route::get('classes/{id}', [\App\Http\Controllers\Api\StudentClassController::class, 'show'])->middleware('permission:student.view');
    Route::match(['put', 'patch'], 'classes/{id}', [\App\Http\Controllers\Api\StudentClassController::class, 'update'])->middleware('permission:student.edit');
    Route::delete('classes/{id}', [\App\Http\Controllers\Api\StudentClassController::class, 'destroy'])->middleware('permission:student.delete');

    // Payments management
    Route::get('payments', [PaymentController::class, 'index'])->middleware('permission:payment.view');
    Route::get('payments/{id}', [PaymentController::class, 'show'])->middleware('permission:payment.view');
    Route::patch('payments/{id}/status', [PaymentController::class, 'updateStatus'])->middleware('permission:payment.view');

    // Payment links management
    Route::post('payment-links/{id}/send', [PaymentLinkController::class, 'sendByEmail'])->middleware('permission:link.send');
    Route::post('payment-links/broadcast', [PaymentLinkController::class, 'broadcast'])->middleware('permission:link.create');
    Route::get('payment-links', [PaymentLinkController::class, 'index'])->middleware('permission:link.view');
    Route::post('payment-links', [PaymentLinkController::class, 'store'])->middleware('permission:link.create');
    Route::get('payment-links/{id}', [PaymentLinkController::class, 'show'])->middleware('permission:link.view');
    Route::match(['put', 'patch'], 'payment-links/{id}', [PaymentLinkController::class, 'update'])->middleware('permission:link.cancel');
    Route::delete('payment-links/{id}', [PaymentLinkController::class, 'destroy'])->middleware('permission:link.cancel');

    // Rapport / KPIs
    Route::get('report/summary', [\App\Http\Controllers\Api\ReportController::class, 'summary'])->middleware('permission:dashboard.view');
});
// public: accessible sans authentification
// Route::get('payment-links/token/{token}', [PaymentLinkController::class, 'publicShow']);
// Route::post('payments/public', [PaymentController::class, 'publicCreate']);
// Route::post('payments/public/checkout', [\App\Http\Controllers\Api\PaymentController::class, 'publicCheckout']);
// Route::get('/payplus/return', [PaymentController::class, 'payplusReturn']);



// Routes publiques sans auth
Route::prefix('payments')->group(function () {
    Route::post('public/checkout', [PaymentController::class, 'publicCheckout']);
    // Vérification du statut d'un paiement (polling depuis le front)
    Route::get('check/{reference}', [PaymentController::class, 'checkStatus']);
});

Route::prefix('payplus')->group(function () {
    Route::get('return', [PaymentController::class, 'payplusReturn']);
    Route::post('webhook', [PaymentController::class, 'payplusWebhook']);
});

Route::prefix('payment-links')->group(function () {
    Route::get('token/{token}', [PaymentLinkController::class, 'publicShow']);
});

// Protégé : profil, liens, paiements
Route::middleware('student.auth')->prefix('student')->group(function () {
    Route::get('profile',       [\App\Http\Controllers\Api\StudentProfileController::class, 'show']);
    Route::get('payment-links', [\App\Http\Controllers\Api\StudentProfileController::class, 'paymentLinks']);
    Route::get('payments',      [\App\Http\Controllers\Api\StudentProfileController::class, 'payments']);
});

