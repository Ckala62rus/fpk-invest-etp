<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ApiController;
use App\Http\Resources\EmailSendLogResource;
use App\Models\EmailSendLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Журнал отправки email (фаза 7.5) — super_admin.
 */
class EmailSendLogController extends ApiController
{
    /**
     * Список записей журнала с пагинацией.
     *
     * @param Request $request Фильтры status, template_id, email
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);

        $query = EmailSendLog::query()
            ->with('template:id,code,name')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('template_id')) {
            $query->where('template_id', (int) $request->input('template_id'));
        }

        if ($request->filled('email')) {
            $query->where('recipient_email', 'ilike', '%'.$request->input('email').'%');
        }

        $paginator = $query->paginate($perPage);

        $paginator->through(
            static fn (EmailSendLog $log): array => (new EmailSendLogResource($log))->resolve(),
        );

        return $this->paginated(
            $paginator,
            'Журнал отправки email.',
        );
    }
}
