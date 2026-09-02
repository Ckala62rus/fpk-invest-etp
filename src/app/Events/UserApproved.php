<?php

namespace App\Events;

use App\Models\Procedure;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Пользователь одобрен администратором (фаза 7.3).
 */
class UserApproved
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param User $user Одобренный пользователь
     * @param User $approvedBy Администратор
     * @return void
     */
    public function __construct(
        public User $user,
        public User $approvedBy,
    ) {
    }
}
