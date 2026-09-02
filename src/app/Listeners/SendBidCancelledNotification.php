<?php

namespace App\Listeners;

use App\Events\BidCancelled;
use App\Jobs\SendBidCancelledMailJob;

/**
 * Уведомление участника об отмене ставки.
 */
class SendBidCancelledNotification
{
    /**
     * @param BidCancelled $event Событие отмены ставки
     * @return void
     */
    public function handle(BidCancelled $event): void
    {
        SendBidCancelledMailJob::dispatch($event->bid->id);
    }
}
