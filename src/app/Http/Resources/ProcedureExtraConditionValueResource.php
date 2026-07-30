<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс значения доп. условия для конкретной ТЗП.
 *
 * @mixin \App\Models\ProcedureExtraConditionValue
 */
class ProcedureExtraConditionValueResource extends JsonResource
{
    /**
     * Преобразует значение условия в JSON.
     *
     * @param Request $request Текущий HTTP-запрос
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'procedure_id' => $this->procedure_id,
            'template_id' => $this->template_id,
            'template' => $this->whenLoaded('template', function () {
                return [
                    'id' => $this->template->id,
                    'name' => $this->template->name,
                    'field_type' => $this->template->field_type?->value,
                ];
            }),
            'value' => $this->value,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
