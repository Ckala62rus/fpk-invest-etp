<?php

namespace App\Http\Controllers;

use App\Enums\ComplaintStatus;
use App\Http\Requests\Api\StoreComplaintRequest;
use App\Http\Resources\ComplaintResource;
use App\Models\Complaint;
use Illuminate\Http\JsonResponse;

/**
 * Приём жалоб с публичной части ЭТП (кнопка «Подать жалобу»).
 *
 * Фаза 4.5: доступен гостю и авторизованному пользователю.
 */
class ComplaintController extends ApiController
{
    /**
     * Создаёт жалобу со статусом «новая».
     *
     * @param StoreComplaintRequest $request Валидированные данные жалобы
     * @return JsonResponse
     */
    public function store(StoreComplaintRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $complaint = Complaint::query()->create([
            'user_id' => $user?->id,
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? ($user?->email),
            'subject' => $data['subject'],
            'message' => $data['message'],
            'status' => ComplaintStatus::New,
        ]);

        return $this->created(
            new ComplaintResource($complaint),
            'Жалоба принята.',
        );
    }
}
