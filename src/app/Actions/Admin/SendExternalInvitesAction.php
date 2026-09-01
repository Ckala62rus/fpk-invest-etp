<?php

namespace App\Actions\Admin;

use App\Enums\ProcedureStatus;
use App\Exceptions\DomainException;
use App\Jobs\SendExternalInvitesJob;
use App\Models\ExternalInviteBatch;
use App\Models\Procedure;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Массовая рассылка приглашений на email незарегистрированным участникам (фаза 6.8).
 */
class SendExternalInvitesAction
{
    /**
     * Создаёт batch и ставит Job на отправку писем.
     *
     * @param Procedure $procedure Целевая ТЗП
     * @param list<string> $emails Список email
     * @param User $initiator Администратор
     * @return ExternalInviteBatch
     *
     * @throws DomainException
     */
    public function execute(Procedure $procedure, array $emails, User $initiator): ExternalInviteBatch
    {
        if (! in_array($procedure->status, [
            ProcedureStatus::Accepting,
            ProcedureStatus::AuctionPending,
        ], true)) {
            throw new DomainException(
                message: 'Приглашения можно отправлять только по опубликованной процедуре.',
                statusCode: 422,
            );
        }

        $normalized = $this->normalizeEmails($emails);

        if ($normalized === []) {
            throw new DomainException(
                message: 'Укажите хотя бы один корректный email.',
                statusCode: 422,
            );
        }

        [$toSend, $skipped] = $this->deduplicate($procedure, $normalized);

        if ($toSend === []) {
            throw new DomainException(
                message: 'Все указанные email уже приглашены или зарегистрированы как участники.',
                statusCode: 422,
            );
        }

        return DB::transaction(function () use ($procedure, $toSend, $skipped, $initiator): ExternalInviteBatch {
            $batch = ExternalInviteBatch::query()->create([
                'procedure_id' => $procedure->id,
                'emails' => $toSend,
                'duplicates_skipped' => $skipped !== [] ? $skipped : null,
                'created_by' => $initiator->id,
                'sent_at' => null,
            ]);

            SendExternalInvitesJob::dispatch($batch->id);

            activity('procedure')
                ->causedBy($initiator)
                ->performedOn($procedure)
                ->event('external_invites_sent')
                ->withProperties([
                    'batch_id' => $batch->id,
                    'count' => count($toSend),
                    'skipped' => count($skipped),
                ])
                ->log('Запущена рассылка внешних приглашений');

            return $batch;
        });
    }

    /**
     * @param list<string> $emails Сырые email
     * @return list<string>
     */
    private function normalizeEmails(array $emails): array
    {
        $result = [];

        foreach ($emails as $email) {
            $normalized = mb_strtolower(trim((string) $email));

            if ($normalized !== '' && filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
                $result[] = $normalized;
            }
        }

        return array_values(array_unique($result));
    }

    /**
     * @param Procedure $procedure ТЗП
     * @param list<string> $emails Нормализованные email
     * @return array{0: list<string>, 1: list<string>}
     */
    private function deduplicate(Procedure $procedure, array $emails): array
    {
        $participantEmails = $procedure->participants()
            ->with('user:id,email')
            ->get()
            ->pluck('user.email')
            ->filter()
            ->map(fn (string $e) => mb_strtolower($e))
            ->all();

        $previousBatchEmails = ExternalInviteBatch::query()
            ->where('procedure_id', $procedure->id)
            ->pluck('emails')
            ->flatten()
            ->map(fn ($e) => mb_strtolower((string) $e))
            ->all();

        $known = array_unique(array_merge($participantEmails, $previousBatchEmails));

        $toSend = [];
        $skipped = [];

        foreach ($emails as $email) {
            if (in_array($email, $known, true)) {
                $skipped[] = $email;
            } else {
                $toSend[] = $email;
                $known[] = $email;
            }
        }

        return [$toSend, $skipped];
    }
}
