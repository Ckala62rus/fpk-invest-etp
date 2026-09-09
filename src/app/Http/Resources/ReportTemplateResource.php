<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Шаблон отчёта.
 *
 * @mixin \App\Models\ReportTemplate
 */
class ReportTemplateResource extends JsonResource
{
    /**
     * @param Request $request HTTP
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'query_config' => $this->query_config,
            'columns' => $this->columns,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
