<?php

namespace App\Support;

use App\Models\Procedure;

/**
 * Данные процедуры для плейсхолдеров email-шаблонов (фаза 7).
 */
final class ProcedureNotificationPayload
{
    /**
     * @param Procedure $procedure ТЗП
     * @return array<string, mixed>
     */
    public static function forProcedure(Procedure $procedure): array
    {
        return [
            'procedure' => [
                'id' => $procedure->id,
                'number' => $procedure->number,
                'title' => $procedure->title,
                'type' => $procedure->type->value,
                'starts_at' => $procedure->starts_at?->toIso8601String(),
                'ends_at' => $procedure->ends_at?->toIso8601String(),
            ],
        ];
    }
}
