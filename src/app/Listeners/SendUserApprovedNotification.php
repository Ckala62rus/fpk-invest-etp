<?php

namespace App\Listeners;

use App\Events\UserApproved;
use App\Jobs\SendUserApprovedMailJob;

/**
 * Ставит в очередь письмо об одобрении регистрации.
 */
class SendUserApprovedNotification
{
    /**
     * @param UserApproved $event Событие одобрения
     * @return void
     */
    public function handle(UserApproved $event): void
    {
        SendUserApprovedMailJob::dispatch($event->user->id, $event->approvedBy->id);
    }
}
