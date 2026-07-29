<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс настраиваемого поля ТЗП для админского API.
 *
 * @mixin \App\Models\ProcedureCustomField
 */
class ProcedureCustomFieldResource extends JsonResource
{
    /**
     * Преобразует настраиваемое поле в JSON.
     *
     * @param Request $request Текущий HTTP-запрос
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'procedure_id' => $this->procedure_id,
            'scope' => $this->scope?->value,
            'scope_label' => $this->scope?->label(),
            'label' => $this->label,
            'field_type' => $this->field_type?->value,
            'field_type_label' => $this->field_type?->label(),
            'options' => $this->options,
            'is_required' => $this->is_required,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
