<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ApiController;
use App\Http\Resources\UserDocumentResource;
use App\Models\User;
use App\Models\UserDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Просмотр и скачивание документов профиля участника администратором.
 *
 * Нужен, чтобы при модерации или разборе КП (коммерческого предложения)
 * открыть учредительные/регистрационные файлы организации без входа под участником.
 */
class AdminUserDocumentController extends ApiController
{
    /**
     * Список документов профиля выбранного пользователя.
     *
     * @param User $user Участник (или другой пользователь ЭТП)
     * @return JsonResponse
     */
    public function index(User $user): JsonResponse
    {
        $documents = UserDocument::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get();

        return $this->success(
            UserDocumentResource::collection($documents)->resolve(),
            'Документы профиля пользователя.',
        );
    }

    /**
     * Скачивает или открывает (inline) документ профиля участника.
     *
     * @param Request $request HTTP-запрос (?inline=1 — просмотр PDF в браузере)
     * @param User $user Владелец документа
     * @param int $document ID записи user_documents
     * @return StreamedResponse
     *
     * @throws NotFoundHttpException Если документ чужой или файл отсутствует на диске
     */
    public function download(Request $request, User $user, int $document): StreamedResponse
    {
        $model = UserDocument::query()
            ->whereKey($document)
            ->where('user_id', $user->id)
            ->first();

        if ($model === null) {
            throw new NotFoundHttpException('Документ пользователя не найден.');
        }

        if (! Storage::disk('local')->exists($model->file_path)) {
            throw new NotFoundHttpException('Файл документа не найден на диске.');
        }

        $inline = $request->boolean('inline');
        $mime = $this->guessMime($model->file_name);

        if ($inline) {
            return Storage::disk('local')->response(
                $model->file_path,
                $model->file_name,
                [
                    'Content-Type' => $mime,
                    'Content-Disposition' => 'inline; filename="'.$this->safeFilename($model->file_name).'"',
                ],
            );
        }

        return Storage::disk('local')->download($model->file_path, $model->file_name);
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
