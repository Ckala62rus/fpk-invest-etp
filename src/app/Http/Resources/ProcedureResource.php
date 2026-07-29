<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Админский ресурс ТЗП (торгово-закупочной процедуры) ЭТП.
 *
 * В отличие от публичного — отдаёт контакты заказчика и служебные поля.
 *
 * @mixin \App\Models\Procedure
 */
class ProcedureResource extends JsonResource
{
    /**
     * Преобразует процедуру в JSON для админки.
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
            'company_id' => $this->company_id,
            'company' => $this->whenLoaded('company', function () {
                return [
                    'id' => $this->company->id,
                    'name' => $this->company->name,
                ];
            }),
            'classifier_category_id' => $this->classifier_category_id,
            'classifier_category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                ];
            }),
            'responsible_user_id' => $this->responsible_user_id,
            'responsible_user' => $this->whenLoaded('responsibleUser', function () {
                return [
                    'id' => $this->responsibleUser->id,
                    'inn' => $this->responsibleUser->inn,
                    'email' => $this->responsibleUser->email,
                ];
            }),
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'inn' => $this->creator->inn,
                    'email' => $this->creator->email,
                ];
            }),
            'customer_contact_name' => $this->customer_contact_name,
            'customer_contact_email' => $this->customer_contact_email,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'published_at' => $this->published_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'results_published' => $this->results_published,
            'storage_years' => $this->storage_years,
            'source_procedure_id' => $this->source_procedure_id,
            'auction_setting' => $this->whenLoaded('auctionSetting', function () {
                if ($this->auctionSetting === null) {
                    return null;
                }

                return [
                    'id' => $this->auctionSetting->id,
                    'bid_mode' => $this->auctionSetting->bid_mode?->value,
                    'auction_mode' => $this->auctionSetting->auction_mode?->value,
                    'extension_minutes' => $this->auctionSetting->extension_minutes,
                    'extension_trigger_minutes' => $this->auctionSetting->extension_trigger_minutes,
                    'idle_timeout_minutes' => $this->auctionSetting->idle_timeout_minutes,
                    'forbid_equal_bids' => $this->auctionSetting->forbid_equal_bids,
                    'winner_mode' => $this->auctionSetting->winner_mode?->value,
                    'only_admitted_from_rfp' => $this->auctionSetting->only_admitted_from_rfp,
                ];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
