<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Запуск отчёта.
 *
 * @mixin \App\Models\ReportRun
 */
class ReportRunResource extends JsonResource
{
    /**
     * @param Request $request HTTP
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'template_id' => $this->template_id,
            'filters' => $this->filters,
            'file_path' => $this->file_path,
            'format' => $this->format?->value,
            'generated_by' => $this->generated_by,
            'generated_at' => $this->generated_at?->toIso8601String(),
        ];
    }
}
