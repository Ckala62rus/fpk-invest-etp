<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\ClassifierCategoryController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\CompanyGroupController;
use App\Http\Controllers\Admin\UserApprovalController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetAdminRequestController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UserDocumentController;
use App\Http\Controllers\UserNotificationSettingController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/test', function (): JsonResponse {
    $message = 'ETP API работает';

    return response()->json([
        'status' => 'ok',
        'message' => $message,
        'timestamp' => now()->toIso8601String(),
        'environment' => app()->environment(),
    ]);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('auth')->group(function (): void {
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:5,1');
    Route::get('/email/verify/{user}/{hash}', VerifyEmailController::class)
        ->middleware('signed')
        ->name('auth.email.verify');
    Route::post('/password/forgot', [PasswordResetController::class, 'forgot'])->middleware('throttle:5,1');
    Route::post('/password/reset', [PasswordResetController::class, 'reset'])->middleware('throttle:5,1');
    Route::post('/password/admin-request', [PasswordResetAdminRequestController::class, 'store'])
        ->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/admin/users', [UserController::class, 'index'])
        ->middleware('role:super_admin|trade_admin|auditor');
    Route::post('/admin/users/{user}/approve', [UserApprovalController::class, 'store'])
        ->middleware('role:super_admin|trade_admin');
    Route::post('/admin/users/{user}/block', [UserController::class, 'block'])
        ->middleware('role:super_admin|trade_admin');
    Route::post('/admin/users/{user}/unblock', [UserController::class, 'unblock'])
        ->middleware('role:super_admin|trade_admin');
    Route::put('/admin/users/{user}/roles', [UserController::class, 'assignRoles'])
        ->middleware('role:super_admin');
    Route::get('/admin/activity-logs', [ActivityLogController::class, 'index'])
        ->middleware('role:super_admin|trade_admin|auditor');
    Route::get('/admin/activity-logs/{activityLog}', [ActivityLogController::class, 'show'])
        ->middleware('role:super_admin|trade_admin|auditor')
        ->whereNumber('activityLog');

    Route::middleware('role:super_admin')->group(function (): void {
        Route::get('/admin/company-groups', [CompanyGroupController::class, 'index']);
        Route::post('/admin/company-groups', [CompanyGroupController::class, 'store']);
        Route::get('/admin/company-groups/{companyGroup}', [CompanyGroupController::class, 'show'])
            ->whereNumber('companyGroup');
        Route::put('/admin/company-groups/{companyGroup}', [CompanyGroupController::class, 'update'])
            ->whereNumber('companyGroup');
        Route::delete('/admin/company-groups/{companyGroup}', [CompanyGroupController::class, 'destroy'])
            ->whereNumber('companyGroup');

        Route::get('/admin/classifier-categories', [ClassifierCategoryController::class, 'index']);
        Route::post('/admin/classifier-categories', [ClassifierCategoryController::class, 'store']);
        Route::get('/admin/classifier-categories/{classifierCategory}', [ClassifierCategoryController::class, 'show'])
            ->whereNumber('classifierCategory');
        Route::put('/admin/classifier-categories/{classifierCategory}', [ClassifierCategoryController::class, 'update'])
            ->whereNumber('classifierCategory');
        Route::delete('/admin/classifier-categories/{classifierCategory}', [ClassifierCategoryController::class, 'destroy'])
            ->whereNumber('classifierCategory');

        Route::get('/admin/companies', [CompanyController::class, 'index']);
        Route::post('/admin/companies', [CompanyController::class, 'store']);
        Route::get('/admin/companies/{company}', [CompanyController::class, 'show'])
            ->whereNumber('company');
        Route::put('/admin/companies/{company}', [CompanyController::class, 'update'])
            ->whereNumber('company');
        Route::delete('/admin/companies/{company}', [CompanyController::class, 'destroy'])
            ->whereNumber('company');
    });

    Route::get('/subscriptions', [SubscriptionController::class, 'show']);
    Route::put('/subscriptions', [SubscriptionController::class, 'update']);
    Route::get('/notification-settings', [UserNotificationSettingController::class, 'show']);
    Route::put('/notification-settings', [UserNotificationSettingController::class, 'update']);

    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/documents', [UserDocumentController::class, 'store']);
});
