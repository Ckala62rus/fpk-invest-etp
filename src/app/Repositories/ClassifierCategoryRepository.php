<?php

namespace App\Repositories;

use App\Contracts\ClassifierCategoryRepositoryInterface;
use App\DTOs\ClassifierCategoryFilterDTO;
use App\Models\ClassifierCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Eloquent-репозиторий категорий классификатора ЭТП (электронной торговой площадки).
 */
class ClassifierCategoryRepository implements ClassifierCategoryRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function paginate(ClassifierCategoryFilterDTO $filter): LengthAwarePaginator
    {
        $query = ClassifierCategory::query()
            ->with('companyGroup')
            ->orderBy('sort_order')
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
            $query->where('name', 'ilike', '%'.trim($filter->search).'%');
        }

        if ($filter->isActive !== null) {
            $query->where('is_active', $filter->isActive);
        }

        return $query->paginate($filter->perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function findById(int $id): ?ClassifierCategory
    {
        return ClassifierCategory::query()->with('companyGroup')->find($id);
    }
}
