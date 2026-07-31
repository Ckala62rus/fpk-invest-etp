<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс решения о допуске / недопуске КП.
 *
 * @mixin \App\Models\AdmissionDecision
 */
class AdmissionDecisionResource extends JsonResource
{
    /**
     * Преобразует решение о допуске в JSON.
     *
     * @param Request $request Текущий HTTP-запрос
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'proposal_id' => $this->proposal_id,
            'decision' => $this->decision?->value,
            'decision_label' => $this->decision?->label(),
            'reason' => $this->reason,
            'decided_by' => $this->decided_by,
            'decided_by_user' => $this->whenLoaded('decidedByUser', function () {
                return [
                    'id' => $this->decidedByUser->id,
                    'inn' => $this->decidedByUser->inn,
                    'email' => $this->decidedByUser->email,
                ];
            }),
            'decided_at' => $this->decided_at?->toIso8601String(),
            'clarification_deadline' => $this->clarification_deadline?->toIso8601String(),
            'proposal_status' => $this->whenLoaded('proposal', fn () => $this->proposal?->status?->value),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
