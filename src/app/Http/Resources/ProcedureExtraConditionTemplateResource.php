<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс шаблона дополнительного условия аукциона.
 *
 * @mixin \App\Models\ProcedureExtraConditionTemplate
 */
class ProcedureExtraConditionTemplateResource extends JsonResource
{
    /**
     * Преобразует шаблон условия в JSON.
     *
     * @param Request $request Текущий HTTP-запрос
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'field_type' => $this->field_type?->value,
            'field_type_label' => $this->field_type?->label(),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
