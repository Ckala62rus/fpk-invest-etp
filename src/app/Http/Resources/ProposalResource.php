<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс заявки (коммерческого предложения) участника.
 *
 * @mixin \App\Models\Proposal
 */
class ProposalResource extends JsonResource
{
    /**
     * Преобразует заявку в JSON.
     *
     * @param Request $request Текущий HTTP-запрос
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'procedure_id' => $this->procedure_id,
            'user_id' => $this->user_id,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'contract_form_agreed_at' => $this->contract_form_agreed_at?->toIso8601String(),
            'version' => $this->version,
            'parent_proposal_id' => $this->parent_proposal_id,
            'procedure' => $this->whenLoaded('procedure', function () {
                return [
                    'id' => $this->procedure->id,
                    'number' => $this->procedure->number,
                    'title' => $this->procedure->title,
                    'type' => $this->procedure->type?->value,
                    'type_label' => $this->procedure->type?->label(),
                ];
            }),
            'field_values' => ProposalFieldValueResource::collection(
                $this->whenLoaded('fieldValues')
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
