<?php

namespace App\Support;

use App\Models\Proposal;
use App\Models\ProposalAccessLog;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Запись обращений к заявкам в proposal_access_logs (аудит просмотров).
 */
final class ProposalAccessLogger
{
    /**
     * Логирует просмотр полного содержимого заявки администратором.
     *
     * @param Proposal $proposal Заявка
     * @param User $user Пользователь
     * @param Request $request HTTP-запрос (IP)
     * @param string $action Действие: view, download
     * @return void
     */
    public static function log(
        Proposal $proposal,
        User $user,
        Request $request,
        string $action = 'view',
    ): void {
        ProposalAccessLog::query()->create([
            'proposal_id' => $proposal->id,
            'user_id' => $user->id,
            'action' => $action,
            'ip_address' => $request->ip(),
        ]);
    }
}
