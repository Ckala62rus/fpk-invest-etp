<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\NotificationMailService;
use App\Support\NotificationTemplateCode;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Письмо участнику об одобрении регистрации (фаза 7.3).
 */
class SendUserApprovedMailJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param int $userId Одобренный пользователь
     * @param int $approvedById Администратор
     * @return void
     */
    public function __construct(
        public int $userId,
        public int $approvedById,
    ) {
    }

    /**
     * @param NotificationMailService $mailService Сервис отправки
     * @return void
     */
    public function handle(NotificationMailService $mailService): void
    {
        $user = User::query()->with('profile')->find($this->userId);
        $approver = User::query()->find($this->approvedById);

        if ($user === null || empty($user->email)) {
            return;
        }

        $mailService->send(
            NotificationTemplateCode::UserApproved,
            $user->email,
            [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'inn' => $user->inn,
                    'name' => $user->profile?->name,
                ],
                'approver' => [
                    'id' => $approver?->id,
                    'email' => $approver?->email,
                ],
            ],
            $user,
        );
    }
}
