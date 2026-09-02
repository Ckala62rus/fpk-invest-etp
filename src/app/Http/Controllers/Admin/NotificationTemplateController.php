<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\Admin\StoreNotificationTemplateRequest;
use App\Http\Requests\Api\Admin\UpdateNotificationTemplateRequest;
use App\Http\Resources\NotificationTemplateResource;
use App\Models\NotificationTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRUD шаблонов email-уведомлений (фаза 7.1) — только super_admin.
 */
class NotificationTemplateController extends ApiController
{
    /**
     * Список шаблонов.
     *
     * @param Request $request Фильтр is_active
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = NotificationTemplate::query()->orderBy('code');

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        return $this->success(
            NotificationTemplateResource::collection($query->get())->resolve(),
            'Шаблоны уведомлений.',
        );
    }

    /**
     * Карточка шаблона.
     *
     * @param NotificationTemplate $notificationTemplate Шаблон
     * @return JsonResponse
     */
    public function show(NotificationTemplate $notificationTemplate): JsonResponse
    {
        return $this->success(
            new NotificationTemplateResource($notificationTemplate),
            'Шаблон уведомления.',
        );
    }

    /**
     * Создание шаблона.
     *
     * @param StoreNotificationTemplateRequest $request Данные шаблона
     * @return JsonResponse
     */
    public function store(StoreNotificationTemplateRequest $request): JsonResponse
    {
        $data = $request->validated();

        $template = NotificationTemplate::query()->create([
            'code' => $data['code'],
            'name' => $data['name'],
            'subject' => $data['subject'],
            'body_html' => $data['body_html'],
            'event_type' => $data['event_type'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return $this->created(
            new NotificationTemplateResource($template),
            'Шаблон уведомления создан.',
        );
    }

    /**
     * Обновление шаблона.
     *
     * @param UpdateNotificationTemplateRequest $request Поля
     * @param NotificationTemplate $notificationTemplate Шаблон
     * @return JsonResponse
     */
    public function update(
        UpdateNotificationTemplateRequest $request,
        NotificationTemplate $notificationTemplate,
    ): JsonResponse {
        $notificationTemplate->update($request->validated());

        return $this->success(
            new NotificationTemplateResource($notificationTemplate->refresh()),
            'Шаблон уведомления обновлён.',
        );
    }

    /**
     * Деактивация шаблона (is_active=false).
     *
     * @param NotificationTemplate $notificationTemplate Шаблон
     * @return JsonResponse
     */
    public function destroy(NotificationTemplate $notificationTemplate): JsonResponse
    {
        $notificationTemplate->update(['is_active' => false]);

        return $this->success(
            new NotificationTemplateResource($notificationTemplate->refresh()),
            'Шаблон уведомления деактивирован.',
        );
    }
}
