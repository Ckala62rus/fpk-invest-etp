<?php

namespace App\Jobs;

use App\Models\AuctionBid;
use App\Services\NotificationMailService;
use App\Support\NotificationTemplateCode;
use App\Support\ProcedureNotificationPayload;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Уведомление участника об отмене ставки (фаза 7.3).
 */
class SendBidCancelledMailJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param int $bidId Ставка
     * @return void
     */
    public function __construct(
        public int $bidId,
    ) {
    }

    /**
     * @param NotificationMailService $mailService Сервис отправки
     * @return void
     */
    public function handle(NotificationMailService $mailService): void
    {
        $bid = AuctionBid::query()
            ->with(['user.profile', 'procedure', 'lot'])
            ->find($this->bidId);

        if ($bid === null || $bid->user === null || empty($bid->user->email)) {
            return;
        }

        $data = array_merge(
            ProcedureNotificationPayload::forProcedure($bid->procedure),
            [
                'bid' => [
                    'id' => $bid->id,
                    'amount' => $bid->amount,
                    'cancel_reason' => $bid->cancel_reason,
                    'cancelled_at' => $bid->cancelled_at?->toIso8601String(),
                ],
                'lot' => [
                    'id' => $bid->lot?->id,
                    'name' => $bid->lot?->name,
                ],
            ],
        );

        $mailService->send(
            NotificationTemplateCode::BidCancelled,
            $bid->user->email,
            $data,
            $bid->user,
        );
    }
}
