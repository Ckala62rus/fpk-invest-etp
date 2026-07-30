<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс приглашённого/допущенного участника ТЗП.
 *
 * @mixin \App\Models\ProcedureParticipant
 */
class ProcedureParticipantResource extends JsonResource
{
    /**
     * Преобразует участника процедуры в JSON.
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
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'inn' => $this->user->inn,
                    'email' => $this->user->email,
                ];
            }),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'admitted_at' => $this->admitted_at?->toIso8601String(),
            'admitted_by' => $this->admitted_by,
            'rejection_reason' => $this->rejection_reason,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
