<?php

namespace App\Events;

use App\Models\AuctionBid;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Ставка аукциона отменена администратором (фаза 7.3 / 8.6).
 */
class BidCancelled
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param AuctionBid $bid Отменённая ставка
     * @return void
     */
    public function __construct(
        public AuctionBid $bid,
    ) {
    }
}
