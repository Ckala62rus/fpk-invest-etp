<?php

namespace App\Http\Controllers\PublicApi;

use App\Enums\ProcedureVisibility;
use App\Http\Controllers\ApiController;
use App\Models\Procedure;
use App\Models\ProcedureDocument;
use App\Repositories\ProcedureRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Публичное скачивание конкурсной документации открытой ТЗП.
 *
 * Нужен участнику на витрине и при подаче КП (коммерческого предложения),
 * чтобы прочитать форму договора и прочие файлы процедуры до согласия.
 */
class ProcedureDocumentController extends ApiController
{
    /**
     * Отдаёт файл документа открытой опубликованной ТЗП.
     *
     * @param Request $request HTTP (?inline=1 — просмотр PDF)
     * @param int $procedure ID ТЗП
     * @param int $document ID procedure_documents
     * @return StreamedResponse
     *
     * @throws NotFoundHttpException Если процедура закрыта/не найдена или файла нет
     */
    public function download(Request $request, int $procedure, int $document): StreamedResponse
    {
        $model = Procedure::query()
            ->whereKey($procedure)
            ->where('visibility', ProcedureVisibility::Open)
            ->whereIn('status', ProcedureRepository::PUBLIC_STATUSES)
            ->first();

        if ($model === null) {
            throw new NotFoundHttpException('Процедура не найдена.');
        }

        $file = ProcedureDocument::query()
            ->whereKey($document)
            ->where('procedure_id', $model->id)
            ->first();

        if ($file === null) {
            throw new NotFoundHttpException('Документ процедуры не найден.');
        }

        if (! Storage::disk('local')->exists($file->file_path)) {
            throw new NotFoundHttpException('Файл документа не найден на диске.');
        }

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
