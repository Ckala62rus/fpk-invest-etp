<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\StoreCorruptionReportRequest;
use App\Http\Resources\CorruptionReportResource;
use App\Models\CorruptionReport;
use Illuminate\Http\JsonResponse;

/**
 * Приём сообщений о коррупции с публичной части ЭТП (антикоррупция в футере).
 *
 * Фаза 4.5: доступен гостю и авторизованному пользователю.
 */
class CorruptionReportController extends ApiController
{
    /**
     * Создаёт сообщение о коррупционной составляющей.
     *
     * @param StoreCorruptionReportRequest $request Валидированные данные
     * @return JsonResponse
     */
    public function store(StoreCorruptionReportRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $report = CorruptionReport::query()->create([
            'user_id' => $user?->id,
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? ($user?->email),
            'message' => $data['message'],
        ]);

        return $this->created(
            new CorruptionReportResource($report),
            'Сообщение о коррупции принято.',
        );
    }
}
