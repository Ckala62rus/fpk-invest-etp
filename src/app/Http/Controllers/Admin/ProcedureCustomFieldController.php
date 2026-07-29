<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProcedureStatus;
use App\Exceptions\DomainException;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\Admin\StoreProcedureCustomFieldRequest;
use App\Http\Requests\Api\Admin\UpdateProcedureCustomFieldRequest;
use App\Http\Resources\ProcedureCustomFieldResource;
use App\Models\Procedure;
use App\Models\ProcedureCustomField;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * CRUD настраиваемых полей ТЗП (торгово-закупочной процедуры).
 *
 * Фаза 5.2: админ задаёт поля формы заявки (текст, select, файл и т.д.).
 * Изменение только у черновика; доступ как у ProcedureController.
 */
class ProcedureCustomFieldController extends ApiController
{
    /**
     * Список настраиваемых полей процедуры.
     *
     * @param Procedure $procedure Родительская ТЗП
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException
     */
    public function index(Procedure $procedure): JsonResponse
    {
        $this->assertCanAccess($procedure);

        $fields = $procedure->customFields()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return $this->success(
            ProcedureCustomFieldResource::collection($fields)->resolve(),
            'Настраиваемые поля процедуры.',
        );
    }

    /**
     * Создаёт настраиваемое поле у черновика ТЗП.
     *
     * @param StoreProcedureCustomFieldRequest $request Валидированные данные
     * @param Procedure $procedure Родительская ТЗП
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException|DomainException
     */
    public function store(StoreProcedureCustomFieldRequest $request, Procedure $procedure): JsonResponse
    {
        $this->assertCanAccess($procedure);
        $this->assertDraft($procedure);

        $data = $request->validated();
        $field = $procedure->customFields()->create([
            'scope' => $data['scope'],
            'label' => $data['label'],
            'field_type' => $data['field_type'],
            'options' => $data['options'] ?? null,
            'is_required' => $data['is_required'] ?? false,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return $this->created(
            new ProcedureCustomFieldResource($field),
            'Настраиваемое поле создано.',
        );
    }

    /**
     * Обновляет настраиваемое поле черновика.
     *
     * @param UpdateProcedureCustomFieldRequest $request Валидированные поля
     * @param Procedure $procedure Родительская ТЗП
     * @param int $customField ID поля
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException|DomainException|NotFoundHttpException
     */
    public function update(
        UpdateProcedureCustomFieldRequest $request,
        Procedure $procedure,
        int $customField,
    ): JsonResponse {
        $this->assertCanAccess($procedure);
        $this->assertDraft($procedure);

        $field = $this->findFieldOrFail($procedure, $customField);
        $field->update($request->validated());

        return $this->success(
            new ProcedureCustomFieldResource($field->refresh()),
            'Настраиваемое поле обновлено.',
        );
    }

    /**
     * Удаляет настраиваемое поле черновика.
     *
     * @param Procedure $procedure Родительская ТЗП
     * @param int $customField ID поля
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException|DomainException|NotFoundHttpException
     */
    public function destroy(Procedure $procedure, int $customField): JsonResponse
    {
        $this->assertCanAccess($procedure);
        $this->assertDraft($procedure);

        $field = $this->findFieldOrFail($procedure, $customField);
        $field->delete();

        return $this->success(
            null,
            'Настраиваемое поле удалено.',
        );
    }

    /**
     * Находит поле, принадлежащее процедуре.
     *
     * @param Procedure $procedure Родительская ТЗП
     * @param int $customFieldId ID поля
     * @return ProcedureCustomField
     *
     * @throws NotFoundHttpException
     */
    private function findFieldOrFail(Procedure $procedure, int $customFieldId): ProcedureCustomField
    {
        $field = $procedure->customFields()->whereKey($customFieldId)->first();

        if ($field === null) {
            throw new NotFoundHttpException('Настраиваемое поле не найдено.');
        }

        return $field;
    }

    /**
     * Редактирование полей разрешено только у черновика.
     *
     * @param Procedure $procedure Целевая ТЗП
     * @return void
     *
     * @throws DomainException
     */
    private function assertDraft(Procedure $procedure): void
    {
        if ($procedure->status !== ProcedureStatus::Draft) {
            throw new DomainException(
                message: 'Настраиваемые поля можно менять только у черновика процедуры.',
                statusCode: 422,
            );
        }
    }

    /**
     * trade_admin без super_admin — только свои ТЗП.
     *
     * @param Procedure $procedure Целевая ТЗП
     * @return void
     *
     * @throws AccessDeniedHttpException
     */
    private function assertCanAccess(Procedure $procedure): void
    {
        /** @var User|null $user */
        $user = request()->user();

        if ($user === null) {
            throw new AccessDeniedHttpException('Требуется аутентификация.');
        }

        if ($user->hasRole('super_admin') || $user->hasRole('auditor')) {
            return;
        }

        if ($user->hasRole('trade_admin') && (int) $procedure->responsible_user_id === (int) $user->id) {
            return;
        }

        throw new AccessDeniedHttpException('Недостаточно прав для доступа к этой процедуре.');
    }
}
