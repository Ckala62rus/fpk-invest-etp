<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс значения настраиваемого поля заявки (КП).
 *
 * @mixin \App\Models\ProposalFieldValue
 */
class ProposalFieldValueResource extends JsonResource
{
    /**
     * Преобразует значение поля в JSON.
     *
     * @param Request $request Текущий HTTP-запрос
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'procedure_custom_field_id' => $this->procedure_custom_field_id,
            'value' => $this->value,
            'label' => $this->whenLoaded('customField', fn () => $this->customField?->label),
            'field_type' => $this->whenLoaded('customField', fn () => $this->customField?->field_type?->value),
        ];
    }
}
