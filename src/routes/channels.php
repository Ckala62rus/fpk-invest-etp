<?php

use App\Broadcasting\AuctionChannelAuthorizer;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Каналы Laravel Echo / Reverb (фаза 8.9)
|--------------------------------------------------------------------------
|
| Echo.private('auction.' + id) → private-auction.{id}
|   тикер: цена, таймер, статус. Без чужих ПДн.
|
| Echo.join('auction.presence.' + id) → presence-auction.presence.{id}
|   кто онлайн с деталями; только администратор (участник получит 403).
|
| Отдельные имена каналов: Laravel снимает префикс presence-/private-
| перед колбэком, поэтому один шаблон auction.{id} нельзя отличить.
*/

/**
 * Тикер аукциона для участников и администраторов.
 */
Broadcast::channel('auction.{procedureId}', function (User $user, int|string $procedureId): bool {
    return app(AuctionChannelAuthorizer::class)->canListenTicker($user, (int) $procedureId);
});

/**
 * Presence только для админки: массив пользователя или false.
 */
Broadcast::channel('auction.presence.{procedureId}', function (User $user, int|string $procedureId): array|false {
    return app(AuctionChannelAuthorizer::class)->presenceUser($user, (int) $procedureId);
});
