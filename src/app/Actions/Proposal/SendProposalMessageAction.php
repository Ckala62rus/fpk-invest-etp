<?php

namespace App\Actions\Proposal;

use App\Enums\ProposalStatus;
use App\Exceptions\DomainException;
use App\Models\Proposal;
use App\Models\ProposalMessage;
use App\Models\User;
use App\Support\Workdays;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Отправляет сообщение в переписке по уточнению коммерческого предложения (КП).
 *
 * Фаза 6.4: участник и админ обмениваются сообщениями; админ может запросить
 * уточнение со сроком не менее 2 рабочих дней.
 */
class SendProposalMessageAction
{
    /**
     * Создаёт сообщение; при запросе уточнения меняет статус заявки.
     *
     * @param Proposal $proposal Заявка
     * @param User $sender Отправитель (участник-владелец или администратор)
     * @param string $message Текст
     * @param array<int, string>|null $attachments Ссылки/имена вложений (JSON)
     * @param bool $requestClarification Админ запрашивает уточнение КП
     * @param Carbon|null $clarificationDeadline Срок корректировки (обязателен при запросе)
     * @return ProposalMessage Созданное сообщение
     *
     * @throws DomainException
     */
    public function execute(
        Proposal $proposal,
        User $sender,
        string $message,
        ?array $attachments = null,
        bool $requestClarification = false,
        ?Carbon $clarificationDeadline = null,
    ): ProposalMessage {
        $this->assertCanSend($proposal, $sender, $requestClarification);

        if ($requestClarification) {
            $this->assertClarificationDeadline($clarificationDeadline);
        }

        return DB::transaction(function () use (
            $proposal,
            $sender,
            $message,
            $attachments,
            $requestClarification,
            $clarificationDeadline,
        ): ProposalMessage {
            if ($requestClarification) {
                $proposal->update(['status' => ProposalStatus::Clarification]);

                // Срок храним в admission_decisions, если решение уже есть;
                // иначе — только в activity log (колонка на proposals в схеме отсутствует).
                $admission = $proposal->admissionDecision;
                if ($admission !== null) {
                    $admission->update([
                        'clarification_deadline' => $clarificationDeadline,
                    ]);
                }

                activity('proposal')
                    ->causedBy($sender)
                    ->performedOn($proposal)
                    ->event('clarification_requested')
                    ->withProperties([
                        'clarification_deadline' => $clarificationDeadline?->toIso8601String(),
                    ])
                    ->log('Запрошено уточнение коммерческого предложения');
            }

            $record = ProposalMessage::query()->create([
                'proposal_id' => $proposal->id,
                'sender_id' => $sender->id,
                'message' => $message,
                'attachments' => $attachments,
            ]);

            return $record->fresh(['sender:id,inn,email']) ?? $record;
        });
    }

    /**
     * Проверяет право на отправку сообщения.
     *
     * @param Proposal $proposal Заявка
     * @param User $sender Отправитель
     * @param bool $requestClarification Флаг запроса уточнения
     * @return void
     *
     * @throws DomainException
     */
    private function assertCanSend(
        Proposal $proposal,
        User $sender,
        bool $requestClarification,
    ): void {
        $isOwner = (int) $proposal->user_id === (int) $sender->id;
        $isAdmin = $sender->hasRole('super_admin')
            || (
                $sender->hasRole('trade_admin')
                && (int) $proposal->procedure->responsible_user_id === (int) $sender->id
            );

        if (! $isOwner && ! $isAdmin) {
            throw new DomainException(
                message: 'Недостаточно прав для переписки по этой заявке.',
                statusCode: 403,
            );
        }

        if ($requestClarification && ! $isAdmin) {
            throw new DomainException(
                message: 'Запросить уточнение может только администратор процедуры.',
                statusCode: 403,
            );
        }

        if ($requestClarification) {
            $allowed = [
                ProposalStatus::Submitted,
                ProposalStatus::UnderReview,
                ProposalStatus::Clarification,
            ];
            if (! in_array($proposal->status, $allowed, true)) {
                throw new DomainException(
                    message: 'Уточнение можно запросить только для поданной или рассматриваемой заявки.',
                    statusCode: 422,
                );
            }

            return;
        }

        // Обычные сообщения — пока заявка в работе по уточнению / рассмотрению
        $chatAllowed = [
            ProposalStatus::Submitted,
            ProposalStatus::UnderReview,
            ProposalStatus::Clarification,
        ];

        if (! in_array($proposal->status, $chatAllowed, true)) {
            throw new DomainException(
                message: 'Переписка по этой заявке недоступна на текущем статусе.',
                statusCode: 422,
            );
        }
    }

    /**
     * Срок уточнения — не менее 2 рабочих дней от сейчас.
     *
     * @param Carbon|null $deadline Срок
     * @return void
     *
     * @throws DomainException
     */
    private function assertClarificationDeadline(?Carbon $deadline): void
    {
        if ($deadline === null) {
            throw new DomainException(
                message: 'Укажите срок уточнения коммерческого предложения.',
                statusCode: 422,
                errors: [
                    'clarification_deadline' => ['Укажите срок уточнения коммерческого предложения.'],
                ],
            );
        }

        if (! Workdays::isAtLeastWorkdaysAhead($deadline, 2)) {
            $min = Workdays::add(now(), 2);

            throw new DomainException(
                message: 'Срок уточнения должен быть не менее 2 рабочих дней.',
                statusCode: 422,
                errors: [
                    'clarification_deadline' => [
                        'Минимальный срок: '.$min->toDateString().' (не менее 2 рабочих дней).',
                    ],
                ],
            );
        }
    }
}
