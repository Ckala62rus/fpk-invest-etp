<?php

namespace App\Repositories;

use App\Contracts\ProcedureRepositoryInterface;
use App\DTOs\ProcedureFilterDTO;
use App\DTOs\PublicProcedureFilterDTO;
use App\Enums\ProcedureStatus;
use App\Enums\ProcedureVisibility;
use App\Models\Procedure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Eloquent-репозиторий ТЗП (торгово-закупочных процедур) ЭТП.
 *
 * Публичная выборка: только открытые (visibility=open), не черновики и не soft-deleted.
 */
class ProcedureRepository implements ProcedureRepositoryInterface
{
    /**
     * Статусы, которые гость может видеть в публичном списке.
     *
     * @var list<string>
     */
    private const PUBLIC_STATUSES = [
        ProcedureStatus::Published->value,
        ProcedureStatus::Accepting->value,
        ProcedureStatus::Review->value,
        ProcedureStatus::AuctionPending->value,
        ProcedureStatus::InProgress->value,
        ProcedureStatus::Completed->value,
    ];

    /**
     * {@inheritdoc}
     */
    public function paginatePublic(PublicProcedureFilterDTO $filter): LengthAwarePaginator
    {
        $query = Procedure::query()
            ->with(['company:id,name', 'category:id,name,company_group_id'])
            ->where('visibility', ProcedureVisibility::Open)
            ->whereIn('status', self::PUBLIC_STATUSES)
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        $this->applySearch($query, $filter);
        $this->applyType($query, $filter);
        $this->applyStatus($query, $filter);
        $this->applyCompany($query, $filter);
        $this->applyCategory($query, $filter);

        return $query->paginate($filter->perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function findPublicById(int $id): ?Procedure
    {
        return Procedure::query()
            ->with(['company:id,name', 'category:id,name,company_group_id'])
            ->whereKey($id)
            ->where('visibility', ProcedureVisibility::Open)
            ->whereIn('status', self::PUBLIC_STATUSES)
            ->first();
    }

    /**
     * Поиск по номеру или названию ТЗП.
     *
     * @param Builder<Procedure> $query Базовый запрос
     * @param PublicProcedureFilterDTO $filter DTO
     * @return void
     */
    private function applySearch(Builder $query, PublicProcedureFilterDTO $filter): void
    {
        if ($filter->search === null || trim($filter->search) === '') {
            return;
        }

        $term = '%'.trim($filter->search).'%';

        $query->where(static function (Builder $q) use ($term): void {
            $q->where('number', 'ilike', $term)
                ->orWhere('title', 'ilike', $term);
        });
    }

    /**
     * Фильтр по типу ТЗП.
     *
     * @param Builder<Procedure> $query Базовый запрос
     * @param PublicProcedureFilterDTO $filter DTO
     * @return void
     */
    private function applyType(Builder $query, PublicProcedureFilterDTO $filter): void
    {
        if ($filter->type === null) {
            return;
        }

        $query->where('type', $filter->type);
    }

    /**
     * Фильтр по статусу (только из публично допустимых).
     *
     * @param Builder<Procedure> $query Базовый запрос
     * @param PublicProcedureFilterDTO $filter DTO
     * @return void
     */
    private function applyStatus(Builder $query, PublicProcedureFilterDTO $filter): void
    {
        if ($filter->status === null) {
            return;
        }

        if (! in_array($filter->status, self::PUBLIC_STATUSES, true)) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where('status', $filter->status);
    }

    /**
     * Фильтр по заказчику.
     *
     * @param Builder<Procedure> $query Базовый запрос
     * @param PublicProcedureFilterDTO $filter DTO
     * @return void
     */
    private function applyCompany(Builder $query, PublicProcedureFilterDTO $filter): void
    {
        if ($filter->companyId === null) {
            return;
        }

        $query->where('company_id', $filter->companyId);
    }

    /**
     * Фильтр по категории классификатора.
     *
     * @param Builder<Procedure> $query Базовый запрос
     * @param PublicProcedureFilterDTO $filter DTO
     * @return void
     */
    private function applyCategory(Builder $query, PublicProcedureFilterDTO $filter): void
    {
        if ($filter->classifierCategoryId === null) {
            return;
        }

        $query->where('classifier_category_id', $filter->classifierCategoryId);
    }

    /**
     * {@inheritdoc}
     */
    public function paginateAdmin(ProcedureFilterDTO $filter): LengthAwarePaginator
    {
        $query = Procedure::query()
            ->with(['company:id,name', 'category:id,name', 'responsibleUser:id,inn,email'])
            ->orderByDesc('id');

        match ($filter->trashed) {
            'with' => $query->withTrashed(),
            'only' => $query->onlyTrashed(),
            default => null,
        };

        if ($filter->search !== null && trim($filter->search) !== '') {
            $term = '%'.trim($filter->search).'%';
            $query->where(static function (Builder $q) use ($term): void {
                $q->where('number', 'ilike', $term)
                    ->orWhere('title', 'ilike', $term);
            });
        }

        if ($filter->type !== null) {
            $query->where('type', $filter->type);
        }

        if ($filter->status !== null) {
            $query->where('status', $filter->status);
        }

        if ($filter->visibility !== null) {
            $query->where('visibility', $filter->visibility);
        }

        if ($filter->companyId !== null) {
            $query->where('company_id', $filter->companyId);
        }

        if ($filter->classifierCategoryId !== null) {
            $query->where('classifier_category_id', $filter->classifierCategoryId);
        }

        if ($filter->responsibleUserId !== null) {
            $query->where('responsible_user_id', $filter->responsibleUserId);
        }

        return $query->paginate($filter->perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function findAdminById(int $id): ?Procedure
    {
        return Procedure::query()
            ->with([
                'company:id,name',
                'category:id,name,company_group_id',
                'responsibleUser:id,inn,email',
                'creator:id,inn,email',
                'auctionSetting',
            ])
            ->find($id);
    }
}
