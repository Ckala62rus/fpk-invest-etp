<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Публичный ресурс ТЗП (торгово-закупочной процедуры) для гостя.
 *
 * Не отдаёт контакты заказчика и служебные поля — только витринные данные.
 *
 * @mixin \App\Models\Procedure
 */
class PublicProcedureResource extends JsonResource
{
    /**
     * Преобразует открытую ТЗП в JSON для публичного списка/карточки.
     *
     * @param Request $request Текущий HTTP-запрос
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'trade_direction' => $this->trade_direction?->value,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'visibility' => $this->visibility?->value,
            'company' => $this->whenLoaded('company', function () {
                return [
                    'id' => $this->company->id,
                    'name' => $this->company->name,
                ];
            }),
            'classifier_category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                ];
            }),
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'published_at' => $this->published_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'results_published' => $this->results_published,
        ];
    }
}
