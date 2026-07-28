<?php

namespace App\Repositories;

use App\Contracts\CompanyRepositoryInterface;
use App\DTOs\CompanyFilterDTO;
use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Eloquent-репозиторий предприятий-заказчиков ЭТП (электронной торговой площадки).
 */
class CompanyRepository implements CompanyRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function paginate(CompanyFilterDTO $filter): LengthAwarePaginator
    {
        $query = Company::query()
            ->with('companyGroup')
            ->orderBy('name')
            ->orderBy('id');

        match ($filter->trashed) {
            'with' => $query->withTrashed(),
            'only' => $query->onlyTrashed(),
            default => null,
        };

        if ($filter->companyGroupId !== null) {
            $query->where('company_group_id', $filter->companyGroupId);
        }

        if ($filter->search !== null && trim($filter->search) !== '') {
            $term = '%'.trim($filter->search).'%';
            $query->where(function ($builder) use ($term): void {
                $builder->where('name', 'ilike', $term)
                    ->orWhere('inn', 'ilike', $term);
            });
        }

        if ($filter->isActive !== null) {
            $query->where('is_active', $filter->isActive);
        }

        if ($filter->isExternal !== null) {
            $query->where('is_external', $filter->isExternal);
        }

        return $query->paginate($filter->perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function findById(int $id): ?Company
    {
        return Company::query()->with('companyGroup')->find($id);
    }
}
