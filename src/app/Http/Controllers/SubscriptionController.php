<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\SyncSubscriptionsRequest;
use App\Models\ClassifierCategory;
use App\Models\CompanyGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Подписки участника на категории и группы компаний ЭТП (электронной торговой площадки).
 *
 * Фаза 3.4: участник управляет только своими подписками (для будущих рассылок о ТЗП).
 */
class SubscriptionController extends ApiController
{
    /**
     * Возвращает текущие подписки аутентифицированного пользователя.
     *
     * @param Request $request Запрос с сессией Sanctum
     * @return JsonResponse JSON с id категорий и групп компаний
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load([
            'categorySubscriptions:id,name,company_group_id',
            'companyGroupSubscriptions:id,name',
        ]);

        return $this->success([
            'categories' => $user->categorySubscriptions->map(static fn (ClassifierCategory $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'company_group_id' => $c->company_group_id,
            ])->values(),
            'company_groups' => $user->companyGroupSubscriptions->map(static fn (CompanyGroup $g) => [
                'id' => $g->id,
                'name' => $g->name,
            ])->values(),
        ], 'Подписки пользователя.');
    }

    /**
     * Синхронизирует подписки (полная замена переданных списков).
     *
     * Если ключ `category_ids` / `company_group_ids` не передан — соответствующий набор не меняется.
     *
     * @param SyncSubscriptionsRequest $request Валидированные id подписок
     * @return JsonResponse Актуальные подписки
     */
    public function update(SyncSubscriptionsRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if (array_key_exists('category_ids', $data)) {
            $user->categorySubscriptions()->sync($data['category_ids'] ?? []);
        }

        if (array_key_exists('company_group_ids', $data)) {
            $user->companyGroupSubscriptions()->sync($data['company_group_ids'] ?? []);
        }

        // Перечитываем связи после sync, чтобы ответ отражал актуальное состояние
        $request->setUserResolver(static fn () => $user->fresh());

        return $this->show($request);
    }
}
