<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ApiController;
use App\Models\Procedure;
use App\Models\Proposal;
use App\Models\ProposalDocument;
use App\Models\User;
use App\Services\ProposalVisibilityService;
use App\Support\ProposalAccessLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Скачивание / просмотр документов КП администратором.
 */
class AdminProposalDocumentController extends ApiController
{
    /**
     * @param ProposalVisibilityService $visibility Правила видимости до/после дедлайна
     * @return void
     */
    public function __construct(
        private readonly ProposalVisibilityService $visibility,
    ) {
    }

    /**
     * Отдаёт файл документа КП (скачивание или inline для PDF в новой вкладке).
     *
     * @param Request $request HTTP-запрос (?inline=1 — Content-Disposition: inline)
     * @param Procedure $procedure Родительская ТЗП
     * @param int $proposal ID КП
     * @param int $document ID документа
     * @return StreamedResponse
     *
     * @throws AccessDeniedHttpException|NotFoundHttpException
     */
    public function download(
        Request $request,
        Procedure $procedure,
        int $proposal,
        int $document,
    ): StreamedResponse {
        $this->assertCanAccess($procedure);

        $model = Proposal::query()
            ->whereKey($proposal)
            ->where('procedure_id', $procedure->id)
            ->with(['procedure', 'user.profile'])
            ->first();

        if ($model === null) {
            throw new NotFoundHttpException('Заявка не найдена в этой процедуре.');
        }

        /** @var User $user */
        $user = $request->user();

        if (! $this->visibility->canViewFullContent($user, $model)) {
            throw new AccessDeniedHttpException(
                'Недостаточно прав для просмотра документов этой заявки.',
            );
        }

        $file = ProposalDocument::query()
            ->whereKey($document)
            ->where('proposal_id', $model->id)
            ->first();

        if ($file === null) {
            throw new NotFoundHttpException('Документ заявки не найден.');
        }

        if (! Storage::disk('local')->exists($file->file_path)) {
            throw new NotFoundHttpException('Файл документа не найден на диске.');
        }

        ProposalAccessLogger::log($model, $user, $request, 'download');

        $inline = $request->boolean('inline');
        $mime = $this->guessMime($file->file_name);

        if ($inline) {
            return Storage::disk('local')->response(
                $file->file_path,
                $file->file_name,
                [
                    'Content-Type' => $mime,
                    'Content-Disposition' => 'inline; filename="'.$this->safeFilename($file->file_name).'"',
                ],
            );
        }

        return Storage::disk('local')->download($file->file_path, $file->file_name);
    }

    /**
     * @param Procedure $procedure ТЗП
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

        if ($user->hasRole('super_admin') || $user->hasRole('auditor')) {
            return;
        }

        if ($user->hasRole('trade_admin') && (int) $procedure->responsible_user_id === (int) $user->id) {
            return;
        }

        throw new AccessDeniedHttpException('Недостаточно прав для доступа к этой процедуре.');
    }

    /**
     * @param string $fileName Имя файла
     * @return string MIME
     */
    private function guessMime(string $fileName): string
    {
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        return match ($ext) {
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'application/octet-stream',
        };
    }

    /**
     * @param string $fileName Имя файла
     * @return string Безопасное имя для заголовка
     */
    private function safeFilename(string $fileName): string
    {
        return str_replace(['"', "\r", "\n"], '', $fileName);
    }
}
