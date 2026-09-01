<?php

namespace App\Jobs;

use App\Mail\ExternalProcedureInviteMail;
use App\Models\ExternalInviteBatch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Отправка внешних приглашений на процедуру (фаза 6.8).
 */
class SendExternalInvitesJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param int $batchId ID ExternalInviteBatch
     * @return void
     */
    public function __construct(
        public int $batchId,
    ) {
    }

    /**
     * @return void
     */
    public function handle(): void
    {
        $batch = ExternalInviteBatch::query()
            ->with('procedure')
            ->find($this->batchId);

        if ($batch === null || $batch->procedure === null) {
            return;
        }

        foreach ($batch->emails as $email) {
            Mail::to($email)->send(new ExternalProcedureInviteMail($batch->procedure));
        }

        $batch->update(['sent_at' => now()]);
    }
}
