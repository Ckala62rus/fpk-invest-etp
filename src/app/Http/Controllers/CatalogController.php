<?php

namespace App\Http\Controllers;

use App\Models\ClassifierCategory;
use App\Models\CompanyGroup;
use Illuminate\Http\JsonResponse;

/**
 * Справочники для подписок участника (только чтение активных записей).
 */
class CatalogController extends ApiController
{
    /**
     * Активные категории классификатора для выбора подписок.
     *
     * @return JsonResponse
     */
    public function categories(): JsonResponse
    {
        $items = ClassifierCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'company_group_id', 'sort_order', 'is_active']);

        return $this->success(
            $items->map(static fn (ClassifierCategory $c): array => [
                'id' => $c->id,
                'name' => $c->name,
                'company_group_id' => $c->company_group_id,
            ])->values()->all(),
            'Категории классификатора.',
        );
    }

    /**
     * Активные группы компаний для выбора подписок.
     *
     * @return JsonResponse
     */
    public function companyGroups(): JsonResponse
    {
        $items = CompanyGroup::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'sort_order', 'is_active']);

        return $this->success(
            $items->map(static fn (CompanyGroup $g): array => [
                'id' => $g->id,
                'name' => $g->name,
            ])->values()->all(),
            'Группы компаний.',
        );
    }
}
