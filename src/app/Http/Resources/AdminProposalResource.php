<?php

namespace App\Http\Resources;

use App\Services\ProposalVisibilityService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс заявки (КП) для админского API с маскированием до дедлайна.
 *
 * @mixin \App\Models\Proposal
 */
class AdminProposalResource extends JsonResource
{
    /**
     * Преобразует заявку в JSON с учётом правил видимости.
     *
     * @param Request $request Текущий HTTP-запрос
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ProposalVisibilityService $visibility */
        $visibility = app(ProposalVisibilityService::class);
        $full = $visibility->canViewFullContent($request->user(), $this->resource);

        // user_id всегда — чтобы из списка КП открыть профиль и документы организации.
        $base = [
            'id' => $this->id,
            'procedure_id' => $this->procedure_id,
            'user_id' => $this->user_id,
            'participant_name' => $this->resolveParticipantName(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'version' => $this->version,
        ];

        if (! $full) {
            return array_merge($base, [
                'content_hidden' => true,
                'content_available_after' => $this->procedure?->ends_at?->toIso8601String(),
            ]);
        }

        return array_merge($base, [
            'contract_form_agreed_at' => $this->contract_form_agreed_at?->toIso8601String(),
            'parent_proposal_id' => $this->parent_proposal_id,
            'field_values' => ProposalFieldValueResource::collection(
                $this->whenLoaded('fieldValues')
            ),
            'documents' => ProposalDocumentResource::collection(
                $this->whenLoaded('documents')
            ),
            'admission_decision' => $this->whenLoaded(
                'admissionDecision',
                fn () => new AdmissionDecisionResource($this->admissionDecision),
            ),
            'content_hidden' => false,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ]);
    }

    /**
     * Наименование участника из профиля или ИНН.
     *
     * @return string|null
     */
    private function resolveParticipantName(): ?string
    {
        $profileName = $this->user?->profile?->name;

        if (is_string($profileName) && trim($profileName) !== '') {
            return $profileName;
        }

        return $this->user?->inn;
    }
}
