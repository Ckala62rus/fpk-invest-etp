<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс записи истории изменений ТЗП.
 *
 * @mixin \App\Models\ProcedureChangeLog
 */
class ProcedureChangeLogResource extends JsonResource
{
    /**
     * Преобразует change log в JSON.
     *
     * @param Request $request Текущий HTTP-запрос
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'procedure_id' => $this->procedure_id,
            'changed_by' => $this->changed_by,
            'changed_by_user' => $this->whenLoaded('changedByUser', function () {
                return [
                    'id' => $this->changedByUser->id,
                    'inn' => $this->changedByUser->inn,
                    'email' => $this->changedByUser->email,
                ];
            }),
            'change_summary' => $this->change_summary,
            'diff' => $this->diff,
            'approval_status' => $this->approval_status?->value,
            'approval_status_label' => $this->approval_status?->label(),
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'deadline_extended_to' => $this->deadline_extended_to?->toIso8601String(),
            'notifications_sent_at' => $this->notifications_sent_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
