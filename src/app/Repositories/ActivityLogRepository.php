<?php

namespace App\Repositories;

use App\Contracts\ActivityLogRepositoryInterface;
use App\DTOs\ActivityLogFilterDTO;
use App\Models\ActivityLog;
use App\Models\AuctionBid;
use App\Models\Procedure;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Eloquent-репозиторий журнала аудита ЭТП (электронной торговой площадки).
 */
class ActivityLogRepository implements ActivityLogRepositoryInterface
{
    /**
     * Короткие alias’ы subject_type → FQCN модели.
     *
     * @var array<string, class-string>
     */
    private const SUBJECT_ALIASES = [
        'user' => User::class,
        'procedure' => Procedure::class,
        'auction_bid' => AuctionBid::class,
    ];

    /**
     * Возвращает постраничный список записей аудита с фильтрами.
     *
     * @param ActivityLogFilterDTO $filter Фильтры канала, события, акторов и дат
     * @return LengthAwarePaginator Пагинатор ActivityLog
     */
    public function paginate(ActivityLogFilterDTO $filter): LengthAwarePaginator
    {
        $query = ActivityLog::query()
            ->with(['causer'])
            ->orderByDesc('id');

        $this->applyFilters($query, $filter);

        return $query->paginate($filter->perPage);
    }

    /**
     * Находит запись аудита по ID с подгрузкой causer.
     *
     * @param int $id Идентификатор записи
     * @return ActivityLog|null
     */
    public function findById(int $id): ?ActivityLog
    {
        return ActivityLog::query()
            ->with(['causer'])
            ->find($id);
    }

    /**
     * Применяет фильтры к запросу журнала.
     *
     * @param Builder<ActivityLog> $query Базовый запрос
     * @param ActivityLogFilterDTO $filter DTO фильтров
     * @return void
     */
    private function applyFilters(Builder $query, ActivityLogFilterDTO $filter): void
    {
        if ($filter->logName !== null && $filter->logName !== '') {
            $query->where('log_name', $filter->logName);
        }

        if ($filter->event !== null && $filter->event !== '') {
            $query->where('event', $filter->event);
        }

        if ($filter->causerId !== null) {
            $query->where('causer_id', $filter->causerId)
                ->where('causer_type', User::class);
        }

        $subjectType = $this->resolveSubjectType($filter->subjectType);

        if ($subjectType !== null) {
            $query->where('subject_type', $subjectType);
        }

        if ($filter->subjectId !== null) {
            $query->where('subject_id', $filter->subjectId);
        }

        if ($filter->dateFrom !== null) {
            $query->where('created_at', '>=', $filter->dateFrom);
        }

        if ($filter->dateTo !== null) {
            $query->where('created_at', '<=', $filter->dateTo);
        }
    }

    /**
     * Преобразует alias или FQCN subject_type в значение для БД.
     *
     * @param string|null $subjectType Alias (user) или полный class name
     * @return string|null Нормализованный subject_type
     */
    private function resolveSubjectType(?string $subjectType): ?string
    {
        if ($subjectType === null || trim($subjectType) === '') {
            return null;
        }

        $key = strtolower(trim($subjectType));

        return self::SUBJECT_ALIASES[$key] ?? $subjectType;
    }
}
