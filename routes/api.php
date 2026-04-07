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

Route::get('check-institution', function () {
    $institution = \App\Models\Institution::query()->orderBy('created_at')->first();

    if (! $institution) {
        return response()->json([
            'exists' => false,
            'institution' => null,
        ]);
    }

    return response()->json([
        'exists' => true,
        'institution' => [
            'id' => $institution->id,
            'name' => $institution->name,
            'logo' => $institution->logo,
            'logo_url' => $institution->logo ? asset('storage/' . $institution->logo) : null,
        ],
    ]);
});

Route::post('register', [\App\Http\Controllers\Api\RegistrationController::class, 'register'])
    ->middleware('throttle:5,1');

Route::post('students/login', [\App\Http\Controllers\Api\StudentAuthController::class, 'requestOtp']);
Route::post('students/verify-otp', [\App\Http\Controllers\Api\StudentAuthController::class, 'verifyOtp']);
Route::post('students/me-by-token', [\App\Http\Controllers\Api\StudentAuthController::class, 'meByToken']);

Route::match(['post','get'], 'admin/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
Route::post('admin/request-otp', [\App\Http\Controllers\Api\AuthController::class, 'requestOtp']);
Route::post('admin/verify-otp', [\App\Http\Controllers\Api\AuthController::class, 'verifyOtp']);

//sanctum
Route::middleware(['auth:sanctum', 'active.annexe', 'active.school_year', 'school_year.lock'])->prefix('admin')->group(function () {
    Route::match(['get','post'], 'logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
    Route::get('me', [\App\Http\Controllers\Api\AuthController::class, 'me']);
    Route::get('me/annexe/{annexeId}', [\App\Http\Controllers\Api\AuthController::class, 'meForAnnexe']);
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
    
    // Students Import/Export (MUST BE BEFORE {id} routes)
    Route::get('students/import/template', [\App\Http\Controllers\Api\StudentImportController::class, 'downloadTemplate'])->middleware('permission:student.create');
    Route::post('students/import/preview', [\App\Http\Controllers\Api\StudentImportController::class, 'preview'])->middleware('permission:student.create');
    Route::post('students/import', [\App\Http\Controllers\Api\StudentImportController::class, 'import'])->middleware('permission:student.create');
    Route::get('students/export', [\App\Http\Controllers\Api\StudentImportController::class, 'export'])->middleware('permission:student.view');
    
    Route::get('students/{id}', [StudentController::class, 'show'])->middleware('permission:student.view');
    Route::match(['put', 'patch'], 'students/{id}', [StudentController::class, 'update'])->middleware('permission:student.edit');
    Route::delete('students/{id}', [StudentController::class, 'destroy'])->middleware('permission:student.delete');
    Route::get('students/{id}/financials', [StudentController::class, 'financials'])->middleware('permission:student.view');
    Route::post('students/{id}/payment-link', [StudentController::class, 'createPaymentLink'])->middleware('permission:link.create');

    // Institutions management
    Route::get('institutions', [\App\Http\Controllers\Api\InstitutionController::class, 'index'])->middleware('permission:annexe.view');
    Route::get('institutions/{id}', [\App\Http\Controllers\Api\InstitutionController::class, 'show'])->middleware('permission:annexe.view');
    Route::match(['put', 'patch'], 'institutions/{id}', [\App\Http\Controllers\Api\InstitutionController::class, 'update'])->middleware('permission:annexe.edit');
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

    // Années scolaires disponibles
    Route::get('school-years', [\App\Http\Controllers\Api\PromotionController::class, 'schoolYears']);
    Route::get('school-years/context', [\App\Http\Controllers\Api\PromotionController::class, 'context']);

    // Promotions / Clôture d'année scolaire
    Route::get('promotions/preview', [\App\Http\Controllers\Api\PromotionController::class, 'preview'])->middleware('permission:student.edit');
    Route::post('promotions',        [\App\Http\Controllers\Api\PromotionController::class, 'execute'])->middleware('permission:student.edit');

    // Level Fees (Barème de scolarité : niveau + filière + année → montant)
    Route::get('level-fees/resolve',        [\App\Http\Controllers\Api\LevelFeeController::class, 'resolve'])->middleware('permission:student.view');
    Route::post('level-fees/copy-year',     [\App\Http\Controllers\Api\LevelFeeController::class, 'copyYear'])->middleware('permission:student.create');
    Route::get('level-fees',                [\App\Http\Controllers\Api\LevelFeeController::class, 'index'])->middleware('permission:student.view');
    Route::post('level-fees',               [\App\Http\Controllers\Api\LevelFeeController::class, 'store'])->middleware('permission:student.create');
    Route::match(['put', 'patch'], 'level-fees/{levelFee}', [\App\Http\Controllers\Api\LevelFeeController::class, 'update'])->middleware('permission:student.edit');
    Route::delete('level-fees/{levelFee}', [\App\Http\Controllers\Api\LevelFeeController::class, 'destroy'])->middleware('permission:student.delete');

    // Payments management
    Route::get('payments', [PaymentController::class, 'index'])->middleware('permission:payment.view');
    Route::get('payments/recent', [PaymentController::class, 'recent'])->middleware('permission:payment.view');
    Route::get('payments/{id}', [PaymentController::class, 'show'])->middleware('permission:payment.view');
    Route::patch('payments/{id}/status', [PaymentController::class, 'updateStatus'])->middleware('permission:payment.view');

    // Payment links management
    Route::post('payment-links/{id}/send', [PaymentLinkController::class, 'sendByEmail'])->middleware('permission:link.send');
    Route::post('payment-links/broadcast', [PaymentLinkController::class, 'broadcast'])->middleware('permission:link.create');
    Route::get('payment-links', [PaymentLinkController::class, 'index'])->middleware('permission:link.view');
    Route::post('payment-links', [PaymentLinkController::class, 'store'])->middleware('permission:link.create');
    Route::get('payment-links/{id}', [PaymentLinkController::class, 'show'])->middleware('permission:link.view');
    Route::get('students/{studentId}/communication-history', [PaymentLinkController::class, 'getCommunicationHistory'])->middleware('permission:student.view');
    Route::match(['put', 'patch'], 'payment-links/{id}', [PaymentLinkController::class, 'update'])->middleware('permission:link.view');
    Route::delete('payment-links/{id}', [PaymentLinkController::class, 'destroy'])->middleware('permission:link.cancel');

    // Rapport / KPIs
    Route::get('report/summary', [\App\Http\Controllers\Api\ReportController::class, 'summary'])->middleware('permission:dashboard.view');

    // Enrollments (historique + ajustement montant scolarité)
    Route::get('enrollments', [\App\Http\Controllers\Api\EnrollmentController::class, 'index'])->middleware('permission:student.view');
    Route::match(['put', 'patch'], 'enrollments/{enrollment}', [\App\Http\Controllers\Api\EnrollmentController::class, 'update'])->middleware('permission:student.edit');

    // Notifications
    Route::get('notifications/unread-count', [\App\Http\Controllers\Api\NotificationController::class, 'unreadCount'])->middleware('permission:notification.view');
    Route::post('notifications/mark-all-as-read', [\App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead'])->middleware('permission:notification.manage');
    Route::delete('notifications/clear-read', [\App\Http\Controllers\Api\NotificationController::class, 'clearRead'])->middleware('permission:notification.manage');
    Route::patch('notifications/{notification}/mark-as-read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead'])->middleware('permission:notification.manage');
    Route::get('notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index'])->middleware('permission:notification.view');
    Route::get('notifications/{notification}', [\App\Http\Controllers\Api\NotificationController::class, 'show'])->middleware('permission:notification.view');
    Route::delete('notifications/{notification}', [\App\Http\Controllers\Api\NotificationController::class, 'destroy'])->middleware('permission:notification.manage');

    // Reminders (Rappels automatiques)
    Route::get('reminders/{reminder}/preview', [\App\Http\Controllers\Api\ReminderController::class, 'preview'])->middleware('permission:reminder.view');
    Route::post('reminders/{reminder}/send-now', [\App\Http\Controllers\Api\ReminderController::class, 'sendNow'])->middleware('permission:reminder.edit');
    Route::patch('reminders/{reminder}/activate', [\App\Http\Controllers\Api\ReminderController::class, 'activate'])->middleware('permission:reminder.edit');
    Route::patch('reminders/{reminder}/deactivate', [\App\Http\Controllers\Api\ReminderController::class, 'deactivate'])->middleware('permission:reminder.edit');
    Route::get('reminders', [\App\Http\Controllers\Api\ReminderController::class, 'index'])->middleware('permission:reminder.view');
    Route::post('reminders', [\App\Http\Controllers\Api\ReminderController::class, 'store'])->middleware('permission:reminder.create');
    Route::get('reminders/{reminder}', [\App\Http\Controllers\Api\ReminderController::class, 'show'])->middleware('permission:reminder.view');
    Route::match(['put', 'patch'], 'reminders/{reminder}', [\App\Http\Controllers\Api\ReminderController::class, 'update'])->middleware('permission:reminder.edit');
    Route::delete('reminders/{reminder}', [\App\Http\Controllers\Api\ReminderController::class, 'destroy'])->middleware('permission:reminder.delete');

    // Dashboard
    Route::prefix('dashboard')->middleware('permission:dashboard.view')->group(function () {
        Route::get('kpis',                [\App\Http\Controllers\Api\DashboardController::class, 'kpis']);
        Route::get('monthly-collections', [\App\Http\Controllers\Api\DashboardController::class, 'monthlyCollections']);
        Route::get('annexe-stats',        [\App\Http\Controllers\Api\DashboardController::class, 'annexeStats']);
    });
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
Route::middleware(['student.auth', 'active.school_year', 'school_year.lock'])->prefix('student')->group(function () {
    Route::get('school-year/context', [\App\Http\Controllers\Api\PromotionController::class, 'context']);
    Route::get('profile',       [\App\Http\Controllers\Api\StudentProfileController::class, 'show']);
    Route::get('payment-links', [\App\Http\Controllers\Api\StudentProfileController::class, 'paymentLinks']);
    Route::get('payments',      [\App\Http\Controllers\Api\StudentProfileController::class, 'payments']);
});

