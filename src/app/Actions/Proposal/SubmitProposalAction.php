<?php

namespace App\Actions\Proposal;

use App\DTOs\SubmitProposalDTO;
use App\Enums\CustomFieldScope;
use App\Enums\CustomFieldType;
use App\Enums\ParticipantStatus;
use App\Enums\ProcedureStatus;
use App\Enums\ProcedureType;
use App\Enums\ProcedureVisibility;
use App\Enums\ProposalStatus;
use App\Enums\UserStatus;
use App\Exceptions\DomainException;
use App\Jobs\SendProposalSubmittedMailJob;
use App\Models\ProcedureCustomField;
use App\Models\Proposal;
use App\Models\ProposalFieldValue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Подаёт коммерческое предложение (КП) участником по запросу предложений.
 *
 * Фаза 6.1: создаёт заявку со статусом submitted, сохраняет значения полей участника.
 */
class SubmitProposalAction
{
    /**
     * Подаёт заявку на ТЗП (торгово-закупочную процедуру).
     *
     * @param SubmitProposalDTO $dto Данные подачи КП
     * @return Proposal Созданная заявка с fieldValues
     *
     * @throws DomainException Если нельзя подать заявку или поля заполнены неверно
     */
    public function execute(SubmitProposalDTO $dto): Proposal
    {
        $this->assertCanSubmit($dto);

        /** @var Collection<int, ProcedureCustomField> $participantFields */
        $participantFields = $dto->procedure->customFields()
            ->where('scope', CustomFieldScope::Participant)
            ->orderBy('sort_order')
            ->get()
            ->keyBy('id');

        $normalizedValues = $this->validateAndNormalizeFieldValues(
            $participantFields,
            $dto->fieldValues,
        );

        $proposal = DB::transaction(function () use ($dto, $normalizedValues): Proposal {
            $now = now();

            $proposal = Proposal::query()->create([
                'procedure_id' => $dto->procedure->id,
                'user_id' => $dto->participant->id,
                'status' => ProposalStatus::Submitted,
                'submitted_at' => $now,
                'contract_form_agreed_at' => $dto->contractFormAgreed ? $now : null,
                'version' => 1,
                'parent_proposal_id' => null,
            ]);

            foreach ($normalizedValues as $fieldId => $value) {
                ProposalFieldValue::query()->create([
                    'proposal_id' => $proposal->id,
                    'procedure_custom_field_id' => $fieldId,
                    'value' => $value,
                ]);
            }

            activity('proposal')
                ->causedBy($dto->participant)
                ->performedOn($proposal)
                ->event('submitted')
                ->withProperties([
                    'procedure_id' => $dto->procedure->id,
                    'procedure_number' => $dto->procedure->number,
                ])
                ->log('Подано коммерческое предложение');

            return $proposal->fresh(['fieldValues.customField']) ?? $proposal;
        });

        SendProposalSubmittedMailJob::dispatch($proposal->id);

        return $proposal;
    }

    /**
     * Проверяет бизнес-правила допуска к подаче КП.
     *
     * @param SubmitProposalDTO $dto Данные подачи
     * @return void
     *
     * @throws DomainException
     */
    private function assertCanSubmit(SubmitProposalDTO $dto): void
    {
        if ($dto->participant->status !== UserStatus::Active) {
            throw new DomainException(
                message: 'Подать заявку может только активный участник.',
                statusCode: 403,
            );
        }

        if (! $dto->participant->hasRole('participant')) {
            throw new DomainException(
                message: 'Подать заявку может только пользователь с ролью участника.',
                statusCode: 403,
            );
        }

        if ($dto->procedure->type !== ProcedureType::RequestForProposal) {
            throw new DomainException(
                message: 'Коммерческое предложение подаётся только на запрос предложений.',
                statusCode: 422,
            );
        }

        if ($dto->procedure->status !== ProcedureStatus::Accepting) {
            throw new DomainException(
                message: 'Приём коммерческих предложений сейчас закрыт.',
                statusCode: 422,
            );
        }

        if ($dto->procedure->ends_at === null || $dto->procedure->ends_at->isPast()) {
            throw new DomainException(
                message: 'Срок приёма коммерческих предложений истёк.',
                statusCode: 422,
            );
        }

        if (! $dto->contractFormAgreed) {
            throw new DomainException(
                message: 'Необходимо согласие с формой договора.',
                statusCode: 422,
                errors: [
                    'contract_form_agreed' => ['Необходимо согласие с формой договора.'],
                ],
            );
        }

        if ($dto->procedure->visibility === ProcedureVisibility::Closed) {
            $isInvited = $dto->procedure->participants()
                ->where('user_id', $dto->participant->id)
                ->whereIn('status', [
                    ParticipantStatus::Invited->value,
                    ParticipantStatus::Admitted->value,
                ])
                ->exists();

            if (! $isInvited) {
                throw new DomainException(
                    message: 'В закрытую процедуру могут подавать заявки только приглашённые участники.',
                    statusCode: 403,
                );
            }
        }

        // Один активный КП на пару процедура+участник (отклонённые не блокируют повторную подачу)
        $hasActive = Proposal::query()
            ->where('procedure_id', $dto->procedure->id)
            ->where('user_id', $dto->participant->id)
            ->where('status', '!=', ProposalStatus::Rejected)
            ->exists();

        if ($hasActive) {
            throw new DomainException(
                message: 'Вы уже подали коммерческое предложение по этой процедуре.',
                statusCode: 422,
            );
        }
    }

    /**
     * Проверяет обязательные поля участника и нормализует значения по типу.
     *
     * Поля типа file заполняются через загрузку документов (фаза 6.2) и здесь не требуются.
     *
     * @param Collection<int, ProcedureCustomField> $fields Поля scope=participant
     * @param array<int, array{procedure_custom_field_id: int, value: string|null}> $submitted Ответы участника
     * @return array<int, string|null> Карта field_id → value
     *
     * @throws DomainException
     */
    private function validateAndNormalizeFieldValues(Collection $fields, array $submitted): array
    {
        $byFieldId = [];
        foreach ($submitted as $row) {
            $fieldId = $row['procedure_custom_field_id'];
            if (! $fields->has($fieldId)) {
                throw new DomainException(
                    message: 'Указано неизвестное поле процедуры.',
                    statusCode: 422,
                    errors: [
                        'field_values' => ['Поле #'.$fieldId.' не относится к этой процедуре или не для участника.'],
                    ],
                );
            }
            $byFieldId[$fieldId] = $row['value'];
        }

        $normalized = [];
        $errors = [];

        foreach ($fields as $field) {
            // Документы КП — отдельно (6.2)
            if ($field->field_type === CustomFieldType::File) {
                continue;
            }

            $raw = $byFieldId[$field->id] ?? null;
            $isEmpty = $raw === null || trim($raw) === '';

            if ($field->is_required && $isEmpty) {
                $errors['field_values.'.$field->id] = [
                    'Поле «'.$field->label.'» обязательно для заполнения.',
                ];
                continue;
            }

            if ($isEmpty) {
                continue;
            }

            try {
                $normalized[$field->id] = $this->normalizeValue($field, $raw);
            } catch (DomainException $e) {
                $errors['field_values.'.$field->id] = [$e->getMessage()];
            }
        }

        if ($errors !== []) {
            throw new DomainException(
                message: 'Проверьте заполнение полей коммерческого предложения.',
                statusCode: 422,
                errors: $errors,
            );
        }

        return $normalized;
    }

    /**
     * Нормализует и проверяет значение поля по его типу.
     *
     * @param ProcedureCustomField $field Метаданные поля
     * @param string $raw Сырое значение от клиента
     * @return string Значение для хранения в TEXT
     *
     * @throws DomainException
     */
    private function normalizeValue(ProcedureCustomField $field, string $raw): string
    {
        $value = trim($raw);

        return match ($field->field_type) {
            CustomFieldType::Text => mb_substr($value, 0, 5000),
            CustomFieldType::Number => $this->assertInteger($field, $value),
            CustomFieldType::Decimal => $this->assertDecimal($field, $value),
            CustomFieldType::Date => $this->assertDate($field, $value),
            CustomFieldType::Boolean => $this->assertBoolean($field, $value),
            CustomFieldType::Select => $this->assertSelectOption($field, $value),
            CustomFieldType::File => throw new DomainException(
                message: 'Поле «'.$field->label.'» заполняется загрузкой документа.',
                statusCode: 422,
            ),
        };
    }

    /**
     * @param ProcedureCustomField $field Поле
     * @param string $value Значение
     * @return string
     *
     * @throws DomainException
     */
    private function assertInteger(ProcedureCustomField $field, string $value): string
    {
        if (! preg_match('/^-?\d+$/', $value)) {
            throw new DomainException(
                message: 'Поле «'.$field->label.'» должно быть целым числом.',
                statusCode: 422,
            );
        }

        return $value;
    }

    /**
     * @param ProcedureCustomField $field Поле
     * @param string $value Значение
     * @return string
     *
     * @throws DomainException
     */
    private function assertDecimal(ProcedureCustomField $field, string $value): string
    {
        if (! is_numeric($value)) {
            throw new DomainException(
                message: 'Поле «'.$field->label.'» должно быть числом.',
                statusCode: 422,
            );
        }

        return $value;
    }

    /**
     * @param ProcedureCustomField $field Поле
     * @param string $value Значение
     * @return string
     *
     * @throws DomainException
     */
    private function assertDate(ProcedureCustomField $field, string $value): string
    {
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        $errors = \DateTimeImmutable::getLastErrors();
        if ($dt === false || ($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0) {
            throw new DomainException(
                message: 'Поле «'.$field->label.'» должно быть датой в формате ГГГГ-ММ-ДД.',
                statusCode: 422,
            );
        }

        return $value;
    }

    /**
     * @param ProcedureCustomField $field Поле
     * @param string $value Значение
     * @return string «1» или «0»
     *
     * @throws DomainException
     */
    private function assertBoolean(ProcedureCustomField $field, string $value): string
    {
        $normalized = mb_strtolower($value);
        if (in_array($normalized, ['1', 'true', 'yes', 'да'], true)) {
            return '1';
        }
        if (in_array($normalized, ['0', 'false', 'no', 'нет'], true)) {
            return '0';
        }

        throw new DomainException(
            message: 'Поле «'.$field->label.'» должно быть логическим значением.',
            statusCode: 422,
        );
    }

    /**
     * @param ProcedureCustomField $field Поле select
     * @param string $value Выбранный вариант
     * @return string
     *
     * @throws DomainException
     */
    private function assertSelectOption(ProcedureCustomField $field, string $value): string
    {
        $options = $field->options ?? [];
        if (! in_array($value, $options, true)) {
            throw new DomainException(
                message: 'Поле «'.$field->label.'»: выберите значение из списка.',
                statusCode: 422,
            );
        }

        return $value;
    }
}
