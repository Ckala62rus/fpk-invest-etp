<?php

namespace App\DTOs;

use App\Http\Requests\Api\SubmitProposalRequest;
use App\Models\Procedure;
use App\Models\User;

/**
 * DTO (Data Transfer Object) подачи коммерческого предложения (КП) участником.
 */
readonly class SubmitProposalDTO
{
    /**
     * @param Procedure $procedure ТЗП (торгово-закупочная процедура) типа запрос предложений
     * @param User $participant Участник, подающий заявку
     * @param bool $contractFormAgreed Согласие с формой договора
     * @param array<int, array{procedure_custom_field_id: int, value: string|null}> $fieldValues Значения полей участника
     * @return void
     */
    public function __construct(
        public Procedure $procedure,
        public User $participant,
        public bool $contractFormAgreed,
        public array $fieldValues,
    ) {
    }

    /**
     * Собирает DTO из маршрута и валидированного FormRequest.
     *
     * @param Procedure $procedure Процедура из route model binding
     * @param User $participant Авторизованный участник
     * @param SubmitProposalRequest $request Валидированное тело запроса
     * @return self
     */
    public static function fromRequest(
        Procedure $procedure,
        User $participant,
        SubmitProposalRequest $request,
    ): self {
        /** @var array<int, array{procedure_custom_field_id: int|string, value?: string|null}> $rawValues */
        $rawValues = $request->validated('field_values') ?? [];

        $fieldValues = [];
        foreach ($rawValues as $row) {
            $fieldValues[] = [
                'procedure_custom_field_id' => (int) $row['procedure_custom_field_id'],
                'value' => array_key_exists('value', $row) && $row['value'] !== null
                    ? (string) $row['value']
                    : null,
            ];
        }

        return new self(
            procedure: $procedure,
            participant: $participant,
            contractFormAgreed: (bool) $request->validated('contract_form_agreed'),
            fieldValues: $fieldValues,
        );
    }
}
