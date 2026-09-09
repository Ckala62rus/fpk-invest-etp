<?php

namespace App\Services;

use App\Enums\ProcedureStatus;
use App\Enums\ProcedureType;
use App\Models\AuctionBid;
use App\Models\AuctionSetting;
use App\Models\Procedure;
use Illuminate\Support\Carbon;

/**
 * Таймер электронного аукциона: автопродление и простой (фаза 8.4–8.5).
 *
 * Продление: ставка в последние extension_trigger_minutes (если null — как extension_minutes)
 * сдвигает ends_at на extension_minutes.
 * Простой: нет активных ставок дольше idle_timeout_minutes — пора завершать.
 * Значение 0 отключает автозавершение по простою.
 */
class AuctionTimerService
{
    /**
     * Нужно ли продлить ends_at после принятой ставки.
     *
     * @param Procedure $procedure Аукцион (с ends_at)
     * @param AuctionSetting $settings Настройки таймера
     * @return bool
     */
    public function shouldExtend(Procedure $procedure, AuctionSetting $settings): bool
    {
        if ($procedure->ends_at === null) {
            return false;
        }

        if ($procedure->ends_at->lte(now())) {
            return false;
        }

        $windowMinutes = $settings->extension_trigger_minutes ?? $settings->extension_minutes;

        if ($windowMinutes < 1) {
            return false;
        }

        return $procedure->ends_at->lessThanOrEqualTo(now()->addMinutes($windowMinutes));
    }

    /**
     * Сдвигает ends_at на extension_minutes; возвращает новое время или null, если не продлевали.
     *
     * Ожидается вызов внутри транзакции с lockForUpdate по процедуре.
     *
     * @param Procedure $procedure Аукцион
     * @param AuctionSetting $settings Настройки
     * @return Carbon|null Новое ends_at
     */
    public function extendIfNeeded(Procedure $procedure, AuctionSetting $settings): ?Carbon
    {
        if (! $this->shouldExtend($procedure, $settings)) {
            return null;
        }

        $newEndsAt = $procedure->ends_at->copy()->addMinutes($settings->extension_minutes);

        $procedure->update(['ends_at' => $newEndsAt]);

        activity('procedure')
            ->performedOn($procedure)
            ->event('auction_extended')
            ->withProperties([
                'ends_at' => $newEndsAt->toIso8601String(),
                'extension_minutes' => $settings->extension_minutes,
            ])
            ->log('Срок аукциона продлён из-за ставки');

        return $newEndsAt;
    }

    /**
     * Момент последней активности торгов: последняя неотменённая ставка либо старт.
     *
     * @param Procedure $procedure Аукцион
     * @return Carbon
     */
    public function lastActivityAt(Procedure $procedure): Carbon
    {
        $lastBidAt = AuctionBid::query()
            ->where('procedure_id', $procedure->id)
            ->where('is_cancelled', false)
            ->max('created_at');

        if ($lastBidAt !== null) {
            return Carbon::parse($lastBidAt);
        }

        return $procedure->starts_at ?? $procedure->published_at ?? $procedure->created_at ?? now();
    }

    /**
     * Истёк ли простой (нет ставок дольше idle_timeout_minutes).
     *
     * При idle_timeout_minutes = 0 автозавершение по простою выключено
     * (торги идут до ends_at или ручного «Финиш»).
     *
     * @param Procedure $procedure Аукцион
     * @param AuctionSetting $settings Настройки
     * @return bool
     */
    public function isIdleTimedOut(Procedure $procedure, AuctionSetting $settings): bool
    {
        if ($settings->is_paused) {
            return false;
        }

        // 0 = выкл: не завершаем по бездействию
        if ((int) $settings->idle_timeout_minutes <= 0) {
            return false;
        }

        $idleMinutes = (int) $settings->idle_timeout_minutes;

        return $this->lastActivityAt($procedure)->lte(now()->subMinutes($idleMinutes));
    }

    /**
     * Истёк ли плановый ends_at (после возможных продлений).
     *
     * @param Procedure $procedure Аукцион
     * @return bool
     */
    public function isDeadlinePassed(Procedure $procedure): bool
    {
        return $procedure->ends_at !== null && $procedure->ends_at->lte(now());
    }

    /**
     * Нужно ли автоматически завершить торги (простой или дедлайн).
     *
     * @param Procedure $procedure Аукцион со связью auctionSetting
     * @return bool
     */
    public function shouldAutoFinish(Procedure $procedure): bool
    {
        if ($procedure->type !== ProcedureType::Auction) {
            return false;
        }

        if ($procedure->status !== ProcedureStatus::InProgress) {
            return false;
        }

        $settings = $procedure->auctionSetting;

        if ($settings === null || $settings->is_paused) {
            return false;
        }

        return $this->isIdleTimedOut($procedure, $settings)
            || $this->isDeadlinePassed($procedure);
    }
}
