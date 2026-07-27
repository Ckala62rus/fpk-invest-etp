<?php

namespace App\DTOs;

use App\Http\Requests\Api\Admin\ListActivityLogsRequest;
use Illuminate\Support\Carbon;

/**
 * DTO фильтров журнала аудита (Spatie Activity Log) для админского API ЭТП.
 */
readonly class ActivityLogFilterDTO
{
    /**
     * @param string|null $logName Канал лога (например user)
     * @param string|null $event Тип события (blocked, roles_assigned, …)
     * @param int|null $causerId ID инициатора действия
     * @param string|null $subjectType Полный class name субъекта или короткий alias (user)
     * @param int|null $subjectId ID субъекта действия
     * @param Carbon|null $dateFrom Нижняя граница created_at (включительно)
     * @param Carbon|null $dateTo Верхняя граница created_at (включительно)
     * @param int $perPage Размер страницы пагинации
     * @return void
     */
    public function __construct(
        public ?string $logName = null,
        public ?string $event = null,
        public ?int $causerId = null,
        public ?string $subjectType = null,
        public ?int $subjectId = null,
        public ?Carbon $dateFrom = null,
        public ?Carbon $dateTo = null,
        public int $perPage = 15,
    ) {
    }

    /**
     * Собирает DTO из валидированного admin-запроса списка аудита.
     *
     * @param ListActivityLogsRequest $request Запрос с фильтрами
     * @return self
     */
    public static function fromRequest(ListActivityLogsRequest $request): self
    {
        $dateFrom = $request->validated('date_from');
        $dateTo = $request->validated('date_to');
        $causerId = $request->validated('causer_id');
        $subjectId = $request->validated('subject_id');

        return new self(
            logName: $request->validated('log_name'),
            event: $request->validated('event'),
            causerId: $causerId !== null ? (int) $causerId : null,
            subjectType: $request->validated('subject_type'),
            subjectId: $subjectId !== null ? (int) $subjectId : null,
            dateFrom: is_string($dateFrom) && $dateFrom !== '' ? Carbon::parse($dateFrom)->startOfDay() : null,
            dateTo: is_string($dateTo) && $dateTo !== '' ? Carbon::parse($dateTo)->endOfDay() : null,
            perPage: (int) ($request->validated('per_page') ?? 15),
        );
    }
}
