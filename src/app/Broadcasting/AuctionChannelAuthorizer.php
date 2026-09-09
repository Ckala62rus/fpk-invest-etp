<?php

namespace App\Broadcasting;

use App\Enums\ParticipantStatus;
use App\Enums\ProcedureType;
use App\Enums\ProcedureVisibility;
use App\Models\Procedure;
use App\Models\User;

/**
 * Кто может слушать WebSocket-каналы аукциона (фаза 8.9).
 *
 * private-auction.{id} — тикер без чужих ПДн.
 * presence-auction.presence.{id} — только админ (кто онлайн с деталями).
 */
class AuctionChannelAuthorizer
{
    /**
     * Доступ к приватному каналу тикера (участник процедуры или админ).
     *
     * @param User $user Текущий пользователь
     * @param int $procedureId ID аукциона
     * @return bool
     */
    public function canListenTicker(User $user, int $procedureId): bool
    {
        $procedure = Procedure::query()->find($procedureId);

        if ($procedure === null || $procedure->type !== ProcedureType::Auction) {
            return false;
        }

        if ($this->isAdminForProcedure($user, $procedure)) {
            return true;
        }

        return $this->isAllowedParticipant($user, $procedure);
    }

    /**
     * Данные для presence-канала; false — участник не должен туда входить.
     *
     * @param User $user Текущий пользователь
     * @param int $procedureId ID аукциона
     * @return array<string, mixed>|false
     */
    public function presenceUser(User $user, int $procedureId): array|false
    {
        $procedure = Procedure::query()->find($procedureId);

        if ($procedure === null || $procedure->type !== ProcedureType::Auction) {
            return false;
        }

        if (! $this->isAdminForProcedure($user, $procedure)) {
            return false;
        }

        return [
            'id' => $user->id,
            'inn' => $user->inn,
            'email' => $user->email,
        ];
    }

    /**
     * @param User $user Пользователь
     * @param Procedure $procedure Аукцион
     * @return bool
     */
    private function isAdminForProcedure(User $user, Procedure $procedure): bool
    {
        if ($user->hasAnyRole(['super_admin', 'auditor'])) {
            return true;
        }

        return $user->hasRole('trade_admin')
            && (int) $procedure->responsible_user_id === (int) $user->id;
    }

    /**
     * @param User $user Пользователь
     * @param Procedure $procedure Аукцион
     * @return bool
     */
    private function isAllowedParticipant(User $user, Procedure $procedure): bool
    {
        if (! $user->hasRole('participant')) {
            return false;
        }

        if ($procedure->visibility === ProcedureVisibility::Open) {
            return true;
        }

        return $procedure->participants()
            ->where('user_id', $user->id)
            ->whereIn('status', [
                ParticipantStatus::Invited,
                ParticipantStatus::Admitted,
            ])
            ->exists();
    }
}
