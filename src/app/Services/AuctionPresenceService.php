<?php

namespace App\Services;

use App\Enums\ParticipantStatus;
use App\Enums\ProcedureType;
use App\Enums\ProcedureVisibility;
use App\Exceptions\DomainException;
use App\Models\AuctionSession;
use App\Models\Procedure;
use App\Models\ProcedureParticipant;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Присутствие на странице аукциона (фаза 8.8).
 *
 * HTTP-heartbeat до WebSocket (фаза 8.9). Участник не видит чужой список онлайн.
 * Админ видит, кто сейчас на странице и кто из приглашённых ни разу не заходил.
 */
class AuctionPresenceService
{
    /**
     * Секунд без heartbeat, после которых считаем пользователя офлайн.
     */
    public const OFFLINE_AFTER_SECONDS = 120;

    /**
     * Фиксирует визит участника на странице аукциона.
     *
     * @param Procedure $procedure Аукцион
     * @param User $user Участник
     * @return AuctionSession
     *
     * @throws DomainException
     */
    public function heartbeat(Procedure $procedure, User $user): AuctionSession
    {
        $this->assertAuction($procedure);
        $this->assertCanVisit($procedure, $user);
        $this->expireStale($procedure);

        $session = AuctionSession::query()->firstOrNew([
            'procedure_id' => $procedure->id,
            'user_id' => $user->id,
        ]);

        if (! $session->exists) {
            $session->first_seen_at = now();
        }

        $session->last_seen_at = now();
        $session->is_online = true;
        $session->save();

        return $session->refresh();
    }

    /**
     * Помечает участника офлайн (уход со страницы).
     *
     * @param Procedure $procedure Аукцион
     * @param User $user Участник
     * @return void
     *
     * @throws DomainException
     */
    public function leave(Procedure $procedure, User $user): void
    {
        $this->assertAuction($procedure);

        AuctionSession::query()
            ->where('procedure_id', $procedure->id)
            ->where('user_id', $user->id)
            ->update([
                'is_online' => false,
                'last_seen_at' => now(),
            ]);
    }

    /**
     * Снимает флаг онлайн у просроченных сессий.
     *
     * @param Procedure $procedure Аукцион
     * @return void
     */
    public function expireStale(Procedure $procedure): void
    {
        AuctionSession::query()
            ->where('procedure_id', $procedure->id)
            ->where('is_online', true)
            ->where('last_seen_at', '<', now()->subSeconds(self::OFFLINE_AFTER_SECONDS))
            ->update(['is_online' => false]);
    }

    /**
     * Снимок присутствия для администратора.
     *
     * @param Procedure $procedure Аукцион
     * @return array{online: list<array<string, mixed>>, online_count: int, invited_never_visited: list<array<string, mixed>>}
     *
     * @throws DomainException
     */
    public function adminSnapshot(Procedure $procedure): array
    {
        $this->assertAuction($procedure);
        $this->expireStale($procedure);

        $onlineSessions = AuctionSession::query()
            ->with('user.profile')
            ->where('procedure_id', $procedure->id)
            ->where('is_online', true)
            ->whereNotNull('user_id')
            ->orderBy('last_seen_at')
            ->get();

        $visitedUserIds = AuctionSession::query()
            ->where('procedure_id', $procedure->id)
            ->whereNotNull('user_id')
            ->pluck('user_id');

        return [
            'online' => $onlineSessions->map(fn (AuctionSession $session): array => $this->mapOnline($session))->values()->all(),
            'online_count' => $onlineSessions->count(),
            'invited_never_visited' => $this->invitedNeverVisited($procedure, $visitedUserIds),
        ];
    }

    /**
     * @param Procedure $procedure Аукцион
     * @return void
     *
     * @throws DomainException
     */
    private function assertAuction(Procedure $procedure): void
    {
        if ($procedure->type !== ProcedureType::Auction) {
            throw new DomainException(
                message: 'Присутствие доступно только для процедур типа auction.',
                statusCode: 422,
            );
        }
    }

    /**
     * Открытый аукцион — любой участник; закрытый — приглашённые/допущенные.
     *
     * @param Procedure $procedure Аукцион
     * @param User $user Участник
     * @return void
     *
     * @throws DomainException
     */
    private function assertCanVisit(Procedure $procedure, User $user): void
    {
        if (! $user->hasRole('participant')) {
            throw new DomainException(
                message: 'Heartbeat присутствия доступен только участнику.',
                statusCode: 403,
            );
        }

        if ($procedure->visibility === ProcedureVisibility::Open) {
            return;
        }

        $invited = $procedure->participants()
            ->where('user_id', $user->id)
            ->whereIn('status', [
                ParticipantStatus::Invited,
                ParticipantStatus::Admitted,
            ])
            ->exists();

        if (! $invited) {
            throw new DomainException(
                message: 'Вы не приглашены к участию в этом аукционе.',
                statusCode: 403,
            );
        }
    }

    /**
     * @param AuctionSession $session Сессия с user.profile
     * @return array<string, mixed>
     */
    private function mapOnline(AuctionSession $session): array
    {
        $user = $session->user;

        return [
            'user_id' => $session->user_id,
            'inn' => $user?->inn,
            'email' => $user?->email,
            'phone' => $user?->profile?->phone,
            'organization_name' => $user?->profile?->name,
            'first_seen_at' => $session->first_seen_at?->toIso8601String(),
            'last_seen_at' => $session->last_seen_at?->toIso8601String(),
        ];
    }

    /**
     * @param Procedure $procedure Аукцион
     * @param Collection<int, mixed> $visitedUserIds Кто хотя бы раз заходил
     * @return list<array<string, mixed>>
     */
    private function invitedNeverVisited(Procedure $procedure, Collection $visitedUserIds): array
    {
        $invited = ProcedureParticipant::query()
            ->with('user.profile')
            ->where('procedure_id', $procedure->id)
            ->whereIn('status', [
                ParticipantStatus::Invited,
                ParticipantStatus::Admitted,
            ])
            ->whereNotIn('user_id', $visitedUserIds->filter()->all())
            ->get();

        return $invited->map(static function (ProcedureParticipant $row): array {
            $user = $row->user;

            return [
                'user_id' => $row->user_id,
                'inn' => $user?->inn,
                'email' => $user?->email,
                'phone' => $user?->profile?->phone,
                'organization_name' => $user?->profile?->name,
            ];
        })->values()->all();
    }
}
