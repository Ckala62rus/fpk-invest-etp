<?php

use App\Http\Controllers\Admin\AdminAuctionBidController;
use App\Http\Controllers\Admin\AdminAuctionPresenceController;
use App\Http\Controllers\Admin\AdminProposalController;
use App\Http\Controllers\Admin\AdminProposalDocumentController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\ActivityLogExportController;
use App\Http\Controllers\Admin\AuctionBidCancelController;
use App\Http\Controllers\Admin\AuctionLifecycleController;
use App\Http\Controllers\Admin\AuctionProtocolController;
use App\Http\Controllers\Admin\AuctionSettingController;
use App\Http\Controllers\Admin\ClassifierCategoryController;
use App\Http\Controllers\Admin\CmsPageController as AdminCmsPageController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\CompanyGroupController;
use App\Http\Controllers\Admin\EmailSendLogController;
use App\Http\Controllers\Admin\EvaluationSurveyTemplateController;
use App\Http\Controllers\Admin\ExternalInviteController;
use App\Http\Controllers\Admin\NotificationTemplateController;
use App\Http\Controllers\Admin\ExtraConditionTemplateController;
use App\Http\Controllers\Admin\ProcedureChangeApprovalController;
use App\Http\Controllers\Admin\ProcedureChangeLogController;
use App\Http\Controllers\Admin\ProcedureController as AdminProcedureController;
use App\Http\Controllers\Admin\ProcedureCustomFieldController;
use App\Http\Controllers\Admin\ProcedureDocumentController;
use App\Http\Controllers\Admin\ProcedureExtraConditionController;
use App\Http\Controllers\Admin\ProcedureLotController;
use App\Http\Controllers\Admin\ProcedureParticipantController;
use App\Http\Controllers\Admin\ProposalAdmissionController;
use App\Http\Controllers\Admin\ProposalMessageController as AdminProposalMessageController;
use App\Http\Controllers\Admin\ReportTemplateController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\AdminUserDocumentController;
use App\Http\Controllers\Admin\UserApprovalController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetAdminRequestController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\AuctionBidController;
use App\Http\Controllers\AuctionPresenceController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\ParticipantAuctionLotController;
use App\Http\Controllers\CorruptionReportController;
use App\Http\Controllers\EvaluationSurveyController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\ProposalDocumentController;
use App\Http\Controllers\ProposalMessageController;
use App\Http\Controllers\PublicApi\CmsPageController as PublicCmsPageController;
use App\Http\Controllers\PublicApi\ProcedureController as PublicProcedureController;
use App\Http\Controllers\ServerTimeController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UserDocumentController;
use App\Http\Controllers\UserNotificationSettingController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
| Назначение каждого маршрута — в documentation/agent-notes/08-api-routes.md
| (этот метод API нужен, чтобы …). Ниже — краткий комментарий у каждого Route::.
*/

// Нужен, чтобы проверить, что REST API ЭТП (электронной торговой площадки) отвечает.
Route::get('/test', function (): JsonResponse {
    $message = 'ETP API работает';

    return response()->json([
        'status' => 'ok',
        'message' => $message,
        'timestamp' => now()->toIso8601String(),
        'environment' => app()->environment(),
    ]);
});

// Нужен, чтобы мониторинг и балансировщик проверили БД и Redis (фаза 11.4).
Route::get('/health', HealthController::class);

// Нужен, чтобы SPA (Single Page Application) получила текущего пользователя Sanctum.
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| Фаза 4 — публичная витрина (без auth)
|--------------------------------------------------------------------------
*/
// Нужен, чтобы фронтенд синхронизировал таймеры приёма заявок и аукциона с часами сервера.
Route::get('/server-time', ServerTimeController::class);

// Нужен, чтобы показать список опубликованных страниц CMS (контент-сайт) без входа.
Route::get('/cms/pages', [PublicCmsPageController::class, 'index']);
// Нужен, чтобы открыть одну опубликованную страницу CMS по ЧПУ.
Route::get('/cms/pages/{slug}', [PublicCmsPageController::class, 'show'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*');

// Нужен, чтобы показать открытые ТЗП (торгово-закупочные процедуры) на витрине.
Route::get('/procedures', [PublicProcedureController::class, 'index']);
// Нужен, чтобы открыть карточку открытой ТЗП без контактов заказчика.
Route::get('/procedures/{procedure}', [PublicProcedureController::class, 'show'])
    ->whereNumber('procedure');

// Нужен, чтобы посетитель отправил жалобу с сайта (антиспам throttle).
Route::post('/complaints', [ComplaintController::class, 'store'])
    ->middleware('throttle:5,1');
// Нужен, чтобы отправить сообщение о коррупции (антиспам throttle).
Route::post('/corruption-reports', [CorruptionReportController::class, 'store'])
    ->middleware('throttle:5,1');

// Нужен, чтобы заказчик открыл опрос качества по ссылке из письма (без входа).
Route::get('/evaluation-surveys/{token}', [EvaluationSurveyController::class, 'show'])
    ->where('token', '[A-Za-z0-9]+');
// Нужен, чтобы заказчик отправил ответы опроса и оценки победителя.
Route::post('/evaluation-surveys/{token}', [EvaluationSurveyController::class, 'store'])
    ->middleware('throttle:20,1')
    ->where('token', '[A-Za-z0-9]+');

Route::prefix('auth')->group(function (): void {
    // Нужен, чтобы войти по ИНН (идентификационный номер налогоплательщика) и паролю.
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1');
    // Нужен, чтобы зарегистрировать участника (pending_email и письмо подтверждения).
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:5,1');
    // Нужен, чтобы подтвердить email по подписанной ссылке из письма.
    Route::get('/email/verify/{user}/{hash}', VerifyEmailController::class)
        ->middleware('signed')
        ->name('auth.email.verify');
    // Нужен, чтобы запросить письмо сброса пароля.
    Route::post('/password/forgot', [PasswordResetController::class, 'forgot'])->middleware('throttle:5,1');
    // Нужен, чтобы задать новый пароль по токену из письма.
    Route::post('/password/reset', [PasswordResetController::class, 'reset'])->middleware('throttle:5,1');
    // Нужен, чтобы попросить администратора сбросить пароль вручную.
    Route::post('/password/admin-request', [PasswordResetAdminRequestController::class, 'store'])
        ->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function (): void {
        // Нужен, чтобы завершить сессию Sanctum.
        Route::post('/logout', [AuthController::class, 'logout']);
        // Нужен, чтобы отдать профиль текущего пользователя для SPA.
        Route::get('/me', [AuthController::class, 'me']);
    });
});

Route::middleware('auth:sanctum')->group(function (): void {
    /*
    |--------------------------------------------------------------------------
    | Фаза 6 — запрос предложений (КП), сторона участника
    |--------------------------------------------------------------------------
    */
        // Нужен, чтобы участник подал КП на запрос предложений.
        Route::post('/procedures/{procedure}/proposals', [ProposalController::class, 'store'])
        ->middleware('role:participant')
        ->whereNumber('procedure');

    Route::middleware('role:participant')->group(function (): void {
        // Нужен, чтобы участник увидел список своих КП без знания ID.
        Route::get('/proposals', [ProposalController::class, 'index']);
        // Нужен, чтобы участник увидел файлы своего КП (коммерческого предложения).
        Route::get('/proposals/{proposal}/documents', [ProposalDocumentController::class, 'index'])
            ->whereNumber('proposal');
        // Нужен, чтобы прикрепить файл к своему КП.
        Route::post('/proposals/{proposal}/documents', [ProposalDocumentController::class, 'store'])
            ->whereNumber('proposal');
        // Нужен, чтобы скачать свой файл КП.
        Route::get('/proposals/{proposal}/documents/{document}/download', [ProposalDocumentController::class, 'download'])
            ->whereNumber('proposal')
            ->whereNumber('document');
        // Нужен, чтобы удалить свой файл КП.
        Route::delete('/proposals/{proposal}/documents/{document}', [ProposalDocumentController::class, 'destroy'])
            ->whereNumber('proposal')
            ->whereNumber('document');

        // Нужен, чтобы открыть переписку по уточнениям КП.
        Route::get('/proposals/{proposal}/messages', [ProposalMessageController::class, 'index'])
            ->whereNumber('proposal');
        // Нужен, чтобы написать уточнение или ответить администратору по КП.
        Route::post('/proposals/{proposal}/messages', [ProposalMessageController::class, 'store'])
            ->whereNumber('proposal');

        // Нужен, чтобы участник посмотрел своё КП.
        Route::get('/proposals/{proposal}', [ProposalController::class, 'show'])
            ->whereNumber('proposal');

        // Нужен, чтобы показать лоты аукциона без победителя и чужих ставок.
        Route::get('/procedures/{procedure}/lots', [ParticipantAuctionLotController::class, 'index'])
            ->whereNumber('procedure');
        // Нужен, чтобы показать только свои ставки по лоту.
        Route::get('/procedures/{procedure}/lots/{lot}/bids', [AuctionBidController::class, 'index'])
            ->whereNumber('procedure')
            ->whereNumber('lot');
        // Нужен, чтобы участник подал ставку (шаг, направление, запрет равных).
        Route::post('/procedures/{procedure}/lots/{lot}/bids', [AuctionBidController::class, 'store'])
            ->middleware('throttle:60,1')
            ->whereNumber('procedure')
            ->whereNumber('lot');

        // Нужен, чтобы сообщить «я на странице аукциона» (онлайн, HTTP до WebSocket).
        Route::post('/procedures/{procedure}/auction/presence/heartbeat', [AuctionPresenceController::class, 'heartbeat'])
            ->middleware('throttle:60,1')
            ->whereNumber('procedure');
        // Нужен, чтобы сообщить «я ушёл со страницы аукциона».
        Route::post('/procedures/{procedure}/auction/presence/leave', [AuctionPresenceController::class, 'leave'])
            ->whereNumber('procedure');
    });

    // Нужен, чтобы найти и отфильтровать пользователей в админке.
    Route::get('/admin/users', [UserController::class, 'index'])
        ->middleware('role:super_admin|trade_admin|auditor');
    // Нужен, чтобы одобрить регистрацию участника.
    Route::post('/admin/users/{user}/approve', [UserApprovalController::class, 'store'])
        ->middleware('role:super_admin|trade_admin');
    // Нужен, чтобы заблокировать учётную запись.
    Route::post('/admin/users/{user}/block', [UserController::class, 'block'])
        ->middleware('role:super_admin|trade_admin');
    // Нужен, чтобы снять блокировку учётной записи.
    Route::post('/admin/users/{user}/unblock', [UserController::class, 'unblock'])
        ->middleware('role:super_admin|trade_admin');
    // Нужен, чтобы назначить роли RBAC (role-based access control).
    Route::put('/admin/users/{user}/roles', [UserController::class, 'assignRoles'])
        ->middleware('role:super_admin');
    // Нужен, чтобы админ увидел документы организации из профиля участника (устав и т.п.).
    Route::get('/admin/users/{user}/documents', [AdminUserDocumentController::class, 'index'])
        ->middleware('role:super_admin|trade_admin|auditor')
        ->whereNumber('user');
    // Нужен, чтобы админ скачал / открыл документ профиля участника (?inline=1 для PDF).
    Route::get('/admin/users/{user}/documents/{document}/download', [AdminUserDocumentController::class, 'download'])
        ->middleware('role:super_admin|trade_admin|auditor')
        ->whereNumber('user')
        ->whereNumber('document');
    // Нужен, чтобы смотреть журнал действий.
    Route::get('/admin/activity-logs', [ActivityLogController::class, 'index'])
        ->middleware('role:super_admin|trade_admin|auditor');
    // Нужен, чтобы выгрузить журнал аудита в CSV.
    Route::get('/admin/activity-logs/export', ActivityLogExportController::class)
        ->middleware('role:super_admin|trade_admin|auditor');
    // Нужен, чтобы открыть одну запись аудита.
    Route::get('/admin/activity-logs/{activityLog}', [ActivityLogController::class, 'show'])
        ->middleware('role:super_admin|trade_admin|auditor')
        ->whereNumber('activityLog');

    // Фаза 5.1 — каркас ТЗП (черновики)
    // Нужен, чтобы показать список процедур (в т.ч. черновики).
    Route::get('/admin/procedures', [AdminProcedureController::class, 'index'])
        ->middleware('role:super_admin|trade_admin|auditor');
    // Нужен, чтобы создать черновик ТЗП или аукциона.
    Route::post('/admin/procedures', [AdminProcedureController::class, 'store'])
        ->middleware('role:super_admin|trade_admin');
    // Нужен, чтобы открыть карточку процедуры в админке.
    Route::get('/admin/procedures/{procedure}', [AdminProcedureController::class, 'show'])
        ->middleware('role:super_admin|trade_admin|auditor')
        ->whereNumber('procedure');
    // Нужен, чтобы править черновик ТЗП.
    Route::put('/admin/procedures/{procedure}', [AdminProcedureController::class, 'update'])
        ->middleware('role:super_admin|trade_admin')
        ->whereNumber('procedure');
    // Нужен, чтобы опубликовать ТЗП (письма и приглашения).
    Route::post('/admin/procedures/{procedure}/publish', [AdminProcedureController::class, 'publish'])
        ->middleware('role:super_admin|trade_admin')
        ->whereNumber('procedure');
    // Нужен, чтобы мягко удалить ТЗП.
    Route::delete('/admin/procedures/{procedure}', [AdminProcedureController::class, 'destroy'])
        ->middleware('role:super_admin')
        ->whereNumber('procedure');
    // Нужен, чтобы восстановить удалённую ТЗП.
    Route::post('/admin/procedures/{procedure}/restore', [AdminProcedureController::class, 'restore'])
        ->middleware('role:super_admin')
        ->whereNumber('procedure');
    // Нужен, чтобы посмотреть историю изменений документации.
    Route::get('/admin/procedures/{procedure}/change-logs', [ProcedureChangeLogController::class, 'index'])
        ->middleware('role:super_admin|trade_admin|auditor')
        ->whereNumber('procedure');
    // Нужен, чтобы согласовать правку документации и продлить срок.
    Route::post('/admin/procedures/{procedure}/change-logs/{changeLog}/approve', [ProcedureChangeApprovalController::class, 'approve'])
        ->middleware('role:super_admin|auditor')
        ->whereNumber('procedure')
        ->whereNumber('changeLog');
    // Нужен, чтобы отклонить правку документации.
    Route::post('/admin/procedures/{procedure}/change-logs/{changeLog}/reject', [ProcedureChangeApprovalController::class, 'reject'])
        ->middleware('role:super_admin|auditor')
        ->whereNumber('procedure')
        ->whereNumber('changeLog');

    // Нужен, чтобы разослать приглашения на внешние email.
    Route::post('/admin/procedures/{procedure}/external-invites', [ExternalInviteController::class, 'store'])
        ->middleware('role:super_admin|trade_admin')
        ->whereNumber('procedure');

    // Фаза 5.2 — настраиваемые поля ТЗП
    // Нужен, чтобы показать настраиваемые поля анкеты ТЗП.
    Route::get('/admin/procedures/{procedure}/custom-fields', [ProcedureCustomFieldController::class, 'index'])
        ->middleware('role:super_admin|trade_admin|auditor')
        ->whereNumber('procedure');
    // Нужен, чтобы добавить поле анкеты ТЗП.
    Route::post('/admin/procedures/{procedure}/custom-fields', [ProcedureCustomFieldController::class, 'store'])
        ->middleware('role:super_admin|trade_admin')
        ->whereNumber('procedure');
    // Нужен, чтобы изменить поле анкеты ТЗП.
    Route::put('/admin/procedures/{procedure}/custom-fields/{customField}', [ProcedureCustomFieldController::class, 'update'])
        ->middleware('role:super_admin|trade_admin')
        ->whereNumber('procedure')
        ->whereNumber('customField');
    // Нужен, чтобы удалить поле анкеты ТЗП.
    Route::delete('/admin/procedures/{procedure}/custom-fields/{customField}', [ProcedureCustomFieldController::class, 'destroy'])
        ->middleware('role:super_admin|trade_admin')
        ->whereNumber('procedure')
        ->whereNumber('customField');

    // Фаза 5.3 — лоты ТЗП
    // Нужен, чтобы показать лоты аукциона в админке.
    Route::get('/admin/procedures/{procedure}/lots', [ProcedureLotController::class, 'index'])
        ->middleware('role:super_admin|trade_admin|auditor')
        ->whereNumber('procedure');
    // Нужен, чтобы добавить лот (название, цена, шаг).
    Route::post('/admin/procedures/{procedure}/lots', [ProcedureLotController::class, 'store'])
        ->middleware('role:super_admin|trade_admin')
        ->whereNumber('procedure');
    // Нужен, чтобы изменить лот черновика.
    Route::put('/admin/procedures/{procedure}/lots/{lot}', [ProcedureLotController::class, 'update'])
        ->middleware('role:super_admin|trade_admin')
        ->whereNumber('procedure')
        ->whereNumber('lot');
    // Нужен, чтобы удалить лот черновика.
    Route::delete('/admin/procedures/{procedure}/lots/{lot}', [ProcedureLotController::class, 'destroy'])
        ->middleware('role:super_admin|trade_admin')
        ->whereNumber('procedure')
        ->whereNumber('lot');

    // Фаза 8.1 — настройки электронного аукциона
    // Нужен, чтобы посмотреть режим ставок, продление и простой.
    Route::get('/admin/procedures/{procedure}/auction-settings', [AuctionSettingController::class, 'show'])
        ->middleware('role:super_admin|trade_admin|auditor')
        ->whereNumber('procedure');
    // Нужен, чтобы настроить торги до старта.
    Route::put('/admin/procedures/{procedure}/auction-settings', [AuctionSettingController::class, 'update'])
        ->middleware('role:super_admin|trade_admin')
        ->whereNumber('procedure');

    // Фаза 8.2 — жизненный цикл торгов
    // Нужен, чтобы запустить торги (ожидание → в процессе).
    Route::post('/admin/procedures/{procedure}/auction/start', [AuctionLifecycleController::class, 'start'])
        ->middleware('role:super_admin|trade_admin')
        ->whereNumber('procedure');
    // Нужен, чтобы поставить торги на паузу.
    Route::post('/admin/procedures/{procedure}/auction/pause', [AuctionLifecycleController::class, 'pause'])
        ->middleware('role:super_admin|trade_admin')
        ->whereNumber('procedure');
    // Нужен, чтобы снять паузу торгов.
    Route::post('/admin/procedures/{procedure}/auction/resume', [AuctionLifecycleController::class, 'resume'])
        ->middleware('role:super_admin|trade_admin')
        ->whereNumber('procedure');
    // Нужен, чтобы завершить торги вручную и назначить победителей лотов.
    Route::post('/admin/procedures/{procedure}/auction/finish', [AuctionLifecycleController::class, 'finish'])
        ->middleware('role:super_admin|trade_admin')
        ->whereNumber('procedure');

    // Нужен, чтобы отменить ставку (не удалять) с причиной.
    Route::post('/admin/procedures/{procedure}/bids/{bid}/cancel', [AuctionBidCancelController::class, 'store'])
        ->middleware('role:super_admin|trade_admin')
        ->whereNumber('procedure')
        ->whereNumber('bid');

    // Нужен, чтобы показать все ставки лота с контактами авторов.
    Route::get('/admin/procedures/{procedure}/lots/{lot}/bids', [AdminAuctionBidController::class, 'index'])
        ->middleware('role:super_admin|trade_admin|auditor')
        ->whereNumber('procedure')
        ->whereNumber('lot');

    // Нужен, чтобы показать, кто онлайн и кто из приглашённых не заходил.
    Route::get('/admin/procedures/{procedure}/auction/presence', [AdminAuctionPresenceController::class, 'show'])
        ->middleware('role:super_admin|trade_admin|auditor')
        ->whereNumber('procedure');

    // Нужен, чтобы показать список PDF-протоколов аукциона.
    Route::get('/admin/procedures/{procedure}/auction/protocols', [AuctionProtocolController::class, 'index'])
        ->middleware('role:super_admin|trade_admin|auditor')
        ->whereNumber('procedure');
    // Нужен, чтобы поставить в очередь повторную генерацию PDF-протокола.
    Route::post('/admin/procedures/{procedure}/auction/protocols', [AuctionProtocolController::class, 'store'])
        ->middleware('role:super_admin|trade_admin')
        ->whereNumber('procedure');
    // Нужен, чтобы скачать PDF-протокол торгов.
    Route::get('/admin/procedures/{procedure}/auction/protocols/{protocol}/download', [AuctionProtocolController::class, 'download'])
        ->middleware('role:super_admin|trade_admin|auditor')
        ->whereNumber('procedure')
        ->whereNumber('protocol');

    // Нужен, чтобы показать шаблоны отчётов.
    Route::get('/admin/report-templates', [ReportTemplateController::class, 'index'])
        ->middleware('role:super_admin|trade_admin|auditor');
    // Нужен, чтобы запустить формирование отчёта (PDF/Excel/Word).
    Route::post('/admin/report-templates/{reportTemplate}/runs', [ReportTemplateController::class, 'run'])
        ->middleware('role:super_admin|trade_admin|auditor')
        ->whereNumber('reportTemplate');
    // Нужен, чтобы посмотреть историю запусков отчёта.
    Route::get('/admin/report-templates/{reportTemplate}/runs', [ReportTemplateController::class, 'runs'])
        ->middleware('role:super_admin|trade_admin|auditor')
        ->whereNumber('reportTemplate');
    // Нужен, чтобы скачать готовый файл отчёта.
    Route::get('/admin/report-runs/{reportRun}/download', [ReportTemplateController::class, 'download'])
        ->middleware('role:super_admin|trade_admin|auditor')
        ->whereNumber('reportRun');

    // Фаза 5.4 — документы ТЗП
    // Нужен, чтобы показать файлы конкурсной документации.
    Route::get('/admin/procedures/{procedure}/documents', [ProcedureDocumentController::class, 'index'])
        ->middleware('role:super_admin|trade_admin|auditor')
        ->whereNumber('procedure');
    // Нужен, чтобы загрузить документ к ТЗП.
    Route::post('/admin/procedures/{procedure}/documents', [ProcedureDocumentController::class, 'store'])
        ->middleware('role:super_admin|trade_admin')
        ->whereNumber('procedure');
    // Нужен, чтобы скачать файл документации.
    Route::get('/admin/procedures/{procedure}/documents/{document}/download', [ProcedureDocumentController::class, 'download'])
        ->middleware('role:super_admin|trade_admin|auditor')
        ->whereNumber('procedure')
        ->whereNumber('document');
    // Нужен, чтобы удалить файл документации.
    Route::delete('/admin/procedures/{procedure}/documents/{document}', [ProcedureDocumentController::class, 'destroy'])
        ->middleware('role:super_admin|trade_admin')
        ->whereNumber('procedure')
        ->whereNumber('document');

    // Фаза 5.5 — участники / приглашения
    // Нужен, чтобы показать приглашённых и допущенных участников.
    Route::get('/admin/procedures/{procedure}/participants', [ProcedureParticipantController::class, 'index'])
        ->middleware('role:super_admin|trade_admin|auditor')
        ->whereNumber('procedure');
    // Нужен, чтобы пригласить участника в закрытую ТЗП.
    Route::post('/admin/procedures/{procedure}/participants', [ProcedureParticipantController::class, 'store'])
        ->middleware('role:super_admin|trade_admin')
        ->whereNumber('procedure');
    // Нужен, чтобы допустить или отклонить участника.
    Route::put('/admin/procedures/{procedure}/participants/{participant}', [ProcedureParticipantController::class, 'update'])
        ->middleware('role:super_admin|trade_admin')
        ->whereNumber('procedure')
        ->whereNumber('participant');
    // Нужен, чтобы снять приглашение участника.
    Route::delete('/admin/procedures/{procedure}/participants/{participant}', [ProcedureParticipantController::class, 'destroy'])
        ->middleware('role:super_admin|trade_admin')
        ->whereNumber('procedure')
        ->whereNumber('participant');

    // Фаза 5.6 — доп. условия процедуры
    // Нужен, чтобы показать доп. условия процедуры.
    Route::get('/admin/procedures/{procedure}/extra-conditions', [ProcedureExtraConditionController::class, 'index'])
        ->middleware('role:super_admin|trade_admin|auditor')
        ->whereNumber('procedure');
    // Нужен, чтобы задать набор доп. условий процедуры.
    Route::put('/admin/procedures/{procedure}/extra-conditions', [ProcedureExtraConditionController::class, 'sync'])
        ->middleware('role:super_admin|trade_admin')
        ->whereNumber('procedure');

    // Нужен, чтобы допустить или отклонить КП (причина обязательна).
    Route::post('/admin/procedures/{procedure}/proposals/{proposal}/admission-decision', [ProposalAdmissionController::class, 'store'])
        ->middleware('role:super_admin|trade_admin')
        ->whereNumber('procedure')
        ->whereNumber('proposal');

    // Нужен, чтобы показать список КП (маскирование до ends_at).
    Route::get('/admin/procedures/{procedure}/proposals', [AdminProposalController::class, 'index'])
        ->middleware('role:super_admin|trade_admin|auditor')
        ->whereNumber('procedure');
    // Нужен, чтобы открыть карточку КП в админке.
    Route::get('/admin/procedures/{procedure}/proposals/{proposal}', [AdminProposalController::class, 'show'])
        ->middleware('role:super_admin|trade_admin|auditor')
        ->whereNumber('procedure')
        ->whereNumber('proposal');
    // Нужен, чтобы скачать или открыть PDF документа КП после дедлайна приёма.
    Route::get('/admin/procedures/{procedure}/proposals/{proposal}/documents/{document}/download', [AdminProposalDocumentController::class, 'download'])
        ->middleware('role:super_admin|trade_admin|auditor')
        ->whereNumber('procedure')
        ->whereNumber('proposal')
        ->whereNumber('document');

    // Нужен, чтобы открыть переписку по КП со стороны админки.
    Route::get('/admin/procedures/{procedure}/proposals/{proposal}/messages', [AdminProposalMessageController::class, 'index'])
        ->middleware('role:super_admin|trade_admin|auditor')
        ->whereNumber('procedure')
        ->whereNumber('proposal');
    // Нужен, чтобы ответить участнику в переписке по КП.
    Route::post('/admin/procedures/{procedure}/proposals/{proposal}/messages', [AdminProposalMessageController::class, 'store'])
        ->middleware('role:super_admin|trade_admin')
        ->whereNumber('procedure')
        ->whereNumber('proposal');

    Route::middleware('role:super_admin')->group(function (): void {
        // Нужен, чтобы прочитать глобальные сроки (правка документации, продление КП).
        Route::get('/admin/settings', [SettingController::class, 'index']);
        // Нужен, чтобы изменить глобальные сроки площадки.
        Route::put('/admin/settings', [SettingController::class, 'update']);

        // Нужен, чтобы показать шаблоны доп. условий.
        Route::get('/admin/extra-condition-templates', [ExtraConditionTemplateController::class, 'index']);
        // Нужен, чтобы создать шаблон доп. условия.
        Route::post('/admin/extra-condition-templates', [ExtraConditionTemplateController::class, 'store']);
        // Нужен, чтобы изменить шаблон доп. условия.
        Route::put('/admin/extra-condition-templates/{extraConditionTemplate}', [ExtraConditionTemplateController::class, 'update'])
            ->whereNumber('extraConditionTemplate');
        // Нужен, чтобы удалить шаблон доп. условия.
        Route::delete('/admin/extra-condition-templates/{extraConditionTemplate}', [ExtraConditionTemplateController::class, 'destroy'])
            ->whereNumber('extraConditionTemplate');

        // Нужен, чтобы показать группы компаний холдинга.
        Route::get('/admin/company-groups', [CompanyGroupController::class, 'index']);
        // Нужен, чтобы создать группу компаний.
        Route::post('/admin/company-groups', [CompanyGroupController::class, 'store']);
        // Нужен, чтобы открыть карточку группы компаний.
        Route::get('/admin/company-groups/{companyGroup}', [CompanyGroupController::class, 'show'])
            ->whereNumber('companyGroup');
        // Нужен, чтобы изменить группу компаний.
        Route::put('/admin/company-groups/{companyGroup}', [CompanyGroupController::class, 'update'])
            ->whereNumber('companyGroup');
        // Нужен, чтобы удалить группу компаний.
        Route::delete('/admin/company-groups/{companyGroup}', [CompanyGroupController::class, 'destroy'])
            ->whereNumber('companyGroup');

        // Нужен, чтобы показать категории классификатора.
        Route::get('/admin/classifier-categories', [ClassifierCategoryController::class, 'index']);
        // Нужен, чтобы создать категорию классификатора.
        Route::post('/admin/classifier-categories', [ClassifierCategoryController::class, 'store']);
        // Нужен, чтобы открыть карточку категории.
        Route::get('/admin/classifier-categories/{classifierCategory}', [ClassifierCategoryController::class, 'show'])
            ->whereNumber('classifierCategory');
        // Нужен, чтобы изменить категорию классификатора.
        Route::put('/admin/classifier-categories/{classifierCategory}', [ClassifierCategoryController::class, 'update'])
            ->whereNumber('classifierCategory');
        // Нужен, чтобы удалить категорию классификатора.
        Route::delete('/admin/classifier-categories/{classifierCategory}', [ClassifierCategoryController::class, 'destroy'])
            ->whereNumber('classifierCategory');

        // Нужен, чтобы показать предприятия-заказчики.
        Route::get('/admin/companies', [CompanyController::class, 'index']);
        // Нужен, чтобы создать заказчика.
        Route::post('/admin/companies', [CompanyController::class, 'store']);
        // Нужен, чтобы открыть карточку заказчика.
        Route::get('/admin/companies/{company}', [CompanyController::class, 'show'])
            ->whereNumber('company');
        // Нужен, чтобы изменить заказчика.
        Route::put('/admin/companies/{company}', [CompanyController::class, 'update'])
            ->whereNumber('company');
        // Нужен, чтобы удалить заказчика.
        Route::delete('/admin/companies/{company}', [CompanyController::class, 'destroy'])
            ->whereNumber('company');

        // Фаза 4.1 — CRUD страниц CMS
        // Нужен, чтобы показать список страниц CMS.
        Route::get('/admin/cms-pages', [AdminCmsPageController::class, 'index']);
        // Нужен, чтобы создать страницу CMS.
        Route::post('/admin/cms-pages', [AdminCmsPageController::class, 'store']);
        // Нужен, чтобы открыть карточку страницы CMS.
        Route::get('/admin/cms-pages/{cmsPage}', [AdminCmsPageController::class, 'show'])
            ->whereNumber('cmsPage');
        // Нужен, чтобы изменить страницу CMS.
        Route::put('/admin/cms-pages/{cmsPage}', [AdminCmsPageController::class, 'update'])
            ->whereNumber('cmsPage');
        // Нужен, чтобы мягко удалить страницу CMS.
        Route::delete('/admin/cms-pages/{cmsPage}', [AdminCmsPageController::class, 'destroy'])
            ->whereNumber('cmsPage');

        // Фаза 7 — шаблоны уведомлений и журнал отправки
        // Нужен, чтобы показать шаблоны писем.
        Route::get('/admin/notification-templates', [NotificationTemplateController::class, 'index']);
        // Нужен, чтобы создать шаблон письма.
        Route::post('/admin/notification-templates', [NotificationTemplateController::class, 'store']);
        // Нужен, чтобы открыть текст шаблона письма.
        Route::get('/admin/notification-templates/{notificationTemplate}', [NotificationTemplateController::class, 'show'])
            ->whereNumber('notificationTemplate');
        // Нужен, чтобы править тему и HTML шаблона письма.
        Route::put('/admin/notification-templates/{notificationTemplate}', [NotificationTemplateController::class, 'update'])
            ->whereNumber('notificationTemplate');
        // Нужен, чтобы деактивировать шаблон письма.
        Route::delete('/admin/notification-templates/{notificationTemplate}', [NotificationTemplateController::class, 'destroy'])
            ->whereNumber('notificationTemplate');

        // Нужен, чтобы смотреть журнал отправки писем.
        Route::get('/admin/email-send-logs', [EmailSendLogController::class, 'index']);

        // Нужен, чтобы показать вопросы опроса качества закупки.
        Route::get('/admin/evaluation-survey-templates', [EvaluationSurveyTemplateController::class, 'index']);
        // Нужен, чтобы добавить вопрос опроса качества.
        Route::post('/admin/evaluation-survey-templates', [EvaluationSurveyTemplateController::class, 'store']);
        // Нужен, чтобы изменить вопрос опроса качества.
        Route::put('/admin/evaluation-survey-templates/{evaluationSurveyTemplate}', [EvaluationSurveyTemplateController::class, 'update'])
            ->whereNumber('evaluationSurveyTemplate');
        // Нужен, чтобы удалить вопрос опроса качества.
        Route::delete('/admin/evaluation-survey-templates/{evaluationSurveyTemplate}', [EvaluationSurveyTemplateController::class, 'destroy'])
            ->whereNumber('evaluationSurveyTemplate');

        // Нужен, чтобы создать шаблон отчёта.
        Route::post('/admin/report-templates', [ReportTemplateController::class, 'store']);
        // Нужен, чтобы изменить шаблон отчёта.
        Route::put('/admin/report-templates/{reportTemplate}', [ReportTemplateController::class, 'update'])
            ->whereNumber('reportTemplate');
        // Нужен, чтобы удалить шаблон отчёта.
        Route::delete('/admin/report-templates/{reportTemplate}', [ReportTemplateController::class, 'destroy'])
            ->whereNumber('reportTemplate');
    });

    // Нужен, чтобы показать подписки на категории и группы компаний.
    Route::get('/subscriptions', [SubscriptionController::class, 'show']);
    // Нужен, чтобы сохранить подписки для писем о новых ТЗП.
    Route::put('/subscriptions', [SubscriptionController::class, 'update']);
    // Нужен, чтобы участник выбрал категории для подписок (без ID вручную).
    Route::get('/catalog/categories', [CatalogController::class, 'categories']);
    // Нужен, чтобы участник выбрал группы компаний для подписок.
    Route::get('/catalog/company-groups', [CatalogController::class, 'companyGroups']);
    // Нужен, чтобы показать, какие email-оповещения включены.
    Route::get('/notification-settings', [UserNotificationSettingController::class, 'show']);
    // Нужен, чтобы включить или выключить типы писем.
    Route::put('/notification-settings', [UserNotificationSettingController::class, 'update']);

    // Нужен, чтобы показать свои регистрационные данные.
    Route::get('/profile', [ProfileController::class, 'show']);
    // Нужен, чтобы обновить ФИО, телефон и организацию в профиле.
    Route::put('/profile', [ProfileController::class, 'update']);
    // Нужен, чтобы показать загруженные документы профиля.
    Route::get('/profile/documents', [UserDocumentController::class, 'index']);
    // Нужен, чтобы загрузить документ к профилю.
    Route::post('/profile/documents', [UserDocumentController::class, 'store']);
    // Нужен, чтобы участник скачал / открыл свой документ профиля (?inline=1 для PDF).
    Route::get('/profile/documents/{document}/download', [UserDocumentController::class, 'download'])
        ->whereNumber('document');
});
