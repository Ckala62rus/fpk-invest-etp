<?php

namespace App\Jobs;

use App\Models\Proposal;
use App\Models\User;
use App\Services\NotificationMailService;
use App\Support\NotificationTemplateCode;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Письма администраторам о новом коммерческом предложении (КП).
 */
class SendProposalSubmittedMailJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param int $proposalId ID поданного КП
     * @return void
     */
    public function __construct(
        public int $proposalId,
    ) {
    }

    /**
     * @param NotificationMailService $mailService Сервис шаблонов
     * @return void
     */
    public function handle(NotificationMailService $mailService): void
    {
        $proposal = Proposal::query()
            ->with(['procedure', 'user.profile'])
            ->find($this->proposalId);

        if ($proposal === null || $proposal->procedure === null) {
            return;
        }

        $admins = User::query()
            ->role(['super_admin', 'trade_admin'])
            ->whereNotNull('email')
            ->get();

        $payload = [
            'proposal' => [
                'id' => $proposal->id,
                'status' => $proposal->status?->value,
            ],
            'procedure' => [
                'id' => $proposal->procedure->id,
                'number' => $proposal->procedure->number,
                'title' => $proposal->procedure->title,
            ],
            'participant' => [
                'id' => $proposal->user?->id,
                'inn' => $proposal->user?->inn,
                'email' => $proposal->user?->email,
                'name' => $proposal->user?->profile?->name,
            ],
        ];

        foreach ($admins as $admin) {
            if (empty($admin->email)) {
                continue;
            }

            $mailService->send(
                NotificationTemplateCode::ProposalSubmitted,
                $admin->email,
                $payload,
                $admin,
            );
        }
    }
}
