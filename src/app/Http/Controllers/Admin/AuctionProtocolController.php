<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ApiController;
use App\Http\Resources\AuctionProtocolResource;
use App\Jobs\GenerateAuctionProtocolJob;
use App\Models\AuctionProtocol;
use App\Models\Procedure;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Протоколы аукциона (фаза 8.11): список, повторная генерация, скачивание PDF.
 */
class AuctionProtocolController extends ApiController
{
    /**
     * Список протоколов процедуры.
     *
     * @param Procedure $procedure Аукцион
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException
     */
    public function index(Procedure $procedure): JsonResponse
    {
        $this->assertCanAccess($procedure);

        $items = AuctionProtocol::query()
            ->where('procedure_id', $procedure->id)
            ->orderByDesc('id')
            ->get();

        return $this->success(
            AuctionProtocolResource::collection($items)->resolve(),
            'Протоколы аукциона.',
        );
    }

    /**
     * Ставит в очередь повторную генерацию PDF.
     *
     * @param Procedure $procedure Аукцион
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException
     */
    public function store(Procedure $procedure): JsonResponse
    {
        $this->assertCanAccess($procedure);

        /** @var User $user */
        $user = request()->user();
        GenerateAuctionProtocolJob::dispatch($procedure->id, $user->id);

        return $this->success(null, 'Генерация протокола поставлена в очередь.');
    }

    /**
     * Скачивает PDF протокола.
     *
     * @param Procedure $procedure Аукцион
     * @param AuctionProtocol $protocol Запись протокола
     * @return StreamedResponse
     *
     * @throws AccessDeniedHttpException|NotFoundHttpException
     */
    public function download(Procedure $procedure, AuctionProtocol $protocol): StreamedResponse
    {
        $this->assertCanAccess($procedure);

        if ((int) $protocol->procedure_id !== (int) $procedure->id) {
            throw new NotFoundHttpException('Протокол не найден.');
        }

        if (! Storage::disk('local')->exists($protocol->file_path)) {
            throw new NotFoundHttpException('Файл протокола не найден.');
        }

        return Storage::disk('local')->download(
            $protocol->file_path,
            'protocol-'.$procedure->id.'.pdf',
        );
    }

    /**
     * @param Procedure $procedure Процедура
     * @return void
     *
     * @throws AccessDeniedHttpException
     */
    private function assertCanAccess(Procedure $procedure): void
    {
        /** @var User|null $user */
        $user = request()->user();

        if ($user === null) {
            throw new AccessDeniedHttpException('Требуется аутентификация.');
        }

        if ($user->hasAnyRole(['super_admin', 'auditor'])) {
            return;
        }

        if ($user->hasRole('trade_admin') && (int) $procedure->responsible_user_id === (int) $user->id) {
            return;
        }

        throw new AccessDeniedHttpException('Недостаточно прав.');
    }
}
