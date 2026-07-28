<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс категории классификатора (2-й уровень) для админского API ЭТП.
 *
 * @mixin \App\Models\ClassifierCategory
 */
class ClassifierCategoryResource extends JsonResource
{
    /**
     * @param Request $request Текущий HTTP-запрос
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_group_id' => $this->company_group_id,
            'name' => $this->name,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'company_group' => $this->whenLoaded('companyGroup', fn () => [
                'id' => $this->companyGroup->id,
                'name' => $this->companyGroup->name,
            ]),
        ];
    }
}
