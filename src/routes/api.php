<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\ClassifierCategoryController;
use App\Http\Controllers\Admin\CmsPageController as AdminCmsPageController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\CompanyGroupController;
use App\Http\Controllers\Admin\ExtraConditionTemplateController;
use App\Http\Controllers\Admin\ProcedureController as AdminProcedureController;
use App\Http\Controllers\Admin\ProcedureCustomFieldController;
use App\Http\Controllers\Admin\ProcedureDocumentController;
use App\Http\Controllers\Admin\ProcedureExtraConditionController;
use App\Http\Controllers\Admin\ProcedureLotController;
use App\Http\Controllers\Admin\ProcedureParticipantController;
use App\Http\Controllers\Admin\UserApprovalController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetAdminRequestController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\CorruptionReportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicApi\CmsPageController as PublicCmsPageController;
use App\Http\Controllers\PublicApi\ProcedureController as PublicProcedureController;
use App\Http\Controllers\ServerTimeController;
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

/*
|--------------------------------------------------------------------------
| Фаза 4 — публичная витрина (без auth)
|--------------------------------------------------------------------------
*/
Route::get('/server-time', ServerTimeController::class);

Route::get('/cms/pages', [PublicCmsPageController::class, 'index']);
Route::get('/cms/pages/{slug}', [PublicCmsPageController::class, 'show'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*');

Route::get('/procedures', [PublicProcedureController::class, 'index']);
Route::get('/procedures/{procedure}', [PublicProcedureController::class, 'show'])
    ->whereNumber('procedure');

Route::post('/complaints', [ComplaintController::class, 'store'])
    ->middleware('throttle:5,1');
Route::post('/corruption-reports', [CorruptionReportController::class, 'store'])
    ->middleware('throttle:5,1');

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

    // Фаза 5.1 — каркас ТЗП (черновики)
    Route::get('/admin/procedures', [AdminProcedureController::class, 'index'])
        ->middleware('role:super_admin|trade_admin|auditor');
    Route::post('/admin/procedures', [AdminProcedureController::class, 'store'])
        ->middleware('role:super_admin|trade_admin');
    Route::get('/admin/procedures/{procedure}', [AdminProcedureController::class, 'show'])
        ->middleware('role:super_admin|trade_admin|auditor')
        ->whereNumber('procedure');
    Route::put('/admin/procedures/{procedure}', [AdminProcedureController::class, 'update'])
        ->middleware('role:super_admin|trade_admin')
        ->whereNumber('procedure');

    // Фаза 5.2 — настраиваемые поля ТЗП
    Route::get('/admin/procedures/{procedure}/custom-fields', [ProcedureCustomFieldController::class, 'index'])
        ->middleware('role:super_admin|trade_admin|auditor')
        ->whereNumber('procedure');
    Route::post('/admin/procedures/{procedure}/custom-fields', [ProcedureCustomFieldController::class, 'store'])
        ->middleware('role:super_admin|trade_admin')
        ->whereNumber('procedure');
    Route::put('/admin/procedures/{procedure}/custom-fields/{customField}', [ProcedureCustomFieldController::class, 'update'])
        ->middleware('role:super_admin|trade_admin')
        ->whereNumber('procedure')
        ->whereNumber('customField');
    Route::delete('/admin/procedures/{procedure}/custom-fields/{customField}', [ProcedureCustomFieldController::class, 'destroy'])
        ->middleware('role:super_admin|trade_admin')
        ->whereNumber('procedure')
        ->whereNumber('customField');

    // Фаза 5.3 — лоты ТЗП
    Route::get('/admin/procedures/{procedure}/lots', [ProcedureLotController::class, 'index'])
        ->middleware('role:super_admin|trade_admin|auditor')
        ->whereNumber('procedure');
    Route::post('/admin/procedures/{procedure}/lots', [ProcedureLotController::class, 'store'])
        ->middleware('role:super_admin|trade_admin')
        ->whereNumber('procedure');
    Route::put('/admin/procedures/{procedure}/lots/{lot}', [ProcedureLotController::class, 'update'])
        ->middleware('role:super_admin|trade_admin')
        ->whereNumber('procedure')
        ->whereNumber('lot');
    Route::delete('/admin/procedures/{procedure}/lots/{lot}', [ProcedureLotController::class, 'destroy'])
        ->middleware('role:super_admin|trade_admin')
        ->whereNumber('procedure')
        ->whereNumber('lot');

    // Фаза 5.4 — документы ТЗП
    Route::get('/admin/procedures/{procedure}/documents', [ProcedureDocumentController::class, 'index'])
        ->middleware('role:super_admin|trade_admin|auditor')
        ->whereNumber('procedure');
    Route::post('/admin/procedures/{procedure}/documents', [ProcedureDocumentController::class, 'store'])
        ->middleware('role:super_admin|trade_admin')
        ->whereNumber('procedure');
    Route::get('/admin/procedures/{procedure}/documents/{document}/download', [ProcedureDocumentController::class, 'download'])
        ->middleware('role:super_admin|trade_admin|auditor')
        ->whereNumber('procedure')
        ->whereNumber('document');
    Route::delete('/admin/procedures/{procedure}/documents/{document}', [ProcedureDocumentController::class, 'destroy'])
        ->middleware('role:super_admin|trade_admin')
        ->whereNumber('procedure')
        ->whereNumber('document');

    // Фаза 5.5 — участники / приглашения
    Route::get('/admin/procedures/{procedure}/participants', [ProcedureParticipantController::class, 'index'])
        ->middleware('role:super_admin|trade_admin|auditor')
        ->whereNumber('procedure');
    Route::post('/admin/procedures/{procedure}/participants', [ProcedureParticipantController::class, 'store'])
        ->middleware('role:super_admin|trade_admin')
        ->whereNumber('procedure');
    Route::put('/admin/procedures/{procedure}/participants/{participant}', [ProcedureParticipantController::class, 'update'])
        ->middleware('role:super_admin|trade_admin')
        ->whereNumber('procedure')
        ->whereNumber('participant');
    Route::delete('/admin/procedures/{procedure}/participants/{participant}', [ProcedureParticipantController::class, 'destroy'])
        ->middleware('role:super_admin|trade_admin')
        ->whereNumber('procedure')
        ->whereNumber('participant');

    // Фаза 5.6 — доп. условия процедуры
    Route::get('/admin/procedures/{procedure}/extra-conditions', [ProcedureExtraConditionController::class, 'index'])
        ->middleware('role:super_admin|trade_admin|auditor')
        ->whereNumber('procedure');
    Route::put('/admin/procedures/{procedure}/extra-conditions', [ProcedureExtraConditionController::class, 'sync'])
        ->middleware('role:super_admin|trade_admin')
        ->whereNumber('procedure');

    Route::middleware('role:super_admin')->group(function (): void {
        Route::get('/admin/extra-condition-templates', [ExtraConditionTemplateController::class, 'index']);
        Route::post('/admin/extra-condition-templates', [ExtraConditionTemplateController::class, 'store']);
        Route::put('/admin/extra-condition-templates/{extraConditionTemplate}', [ExtraConditionTemplateController::class, 'update'])
            ->whereNumber('extraConditionTemplate');
        Route::delete('/admin/extra-condition-templates/{extraConditionTemplate}', [ExtraConditionTemplateController::class, 'destroy'])
            ->whereNumber('extraConditionTemplate');

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

        // Фаза 4.1 — CRUD страниц CMS
        Route::get('/admin/cms-pages', [AdminCmsPageController::class, 'index']);
        Route::post('/admin/cms-pages', [AdminCmsPageController::class, 'store']);
        Route::get('/admin/cms-pages/{cmsPage}', [AdminCmsPageController::class, 'show'])
            ->whereNumber('cmsPage');
        Route::put('/admin/cms-pages/{cmsPage}', [AdminCmsPageController::class, 'update'])
            ->whereNumber('cmsPage');
        Route::delete('/admin/cms-pages/{cmsPage}', [AdminCmsPageController::class, 'destroy'])
            ->whereNumber('cmsPage');
    });

    Route::get('/subscriptions', [SubscriptionController::class, 'show']);
    Route::put('/subscriptions', [SubscriptionController::class, 'update']);
    Route::get('/notification-settings', [UserNotificationSettingController::class, 'show']);
    Route::put('/notification-settings', [UserNotificationSettingController::class, 'update']);

    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/documents', [UserDocumentController::class, 'store']);
});
