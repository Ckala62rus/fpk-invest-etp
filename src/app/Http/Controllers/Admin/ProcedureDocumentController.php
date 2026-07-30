<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProcedureStatus;
use App\Exceptions\DomainException;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\Admin\StoreProcedureDocumentRequest;
use App\Http\Resources\ProcedureDocumentResource;
use App\Models\Procedure;
use App\Models\ProcedureDocument;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Конкурсная документация ТЗП (торгово-закупочной процедуры).
 *
 * Фаза 5.4: загрузка файлов на диск `local` (как UserDocument), метаданные в
 * `procedure_documents` со версионированием. Spatie Media Library не подключаем —
 * схема БД уже описывает отдельную таблицу документов.
 */
class ProcedureDocumentController extends ApiController
{
    /**
     * Список документов процедуры (без soft-deleted).
     *
     * @param Procedure $procedure Родительская ТЗП
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException
     */
    public function index(Procedure $procedure): JsonResponse
    {
        $this->assertCanAccess($procedure);

        $documents = $procedure->documents()
            ->with('uploader:id,inn,email')
            ->orderByDesc('version')
            ->orderByDesc('id')
            ->get();

        return $this->success(
            ProcedureDocumentResource::collection($documents)->resolve(),
            'Документы процедуры.',
        );
    }

    /**
     * Загружает файл документации и создаёт запись с новой версией.
     *
     * @param StoreProcedureDocumentRequest $request Файл document
     * @param Procedure $procedure Родительская ТЗП
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException|DomainException
     */
    public function store(StoreProcedureDocumentRequest $request, Procedure $procedure): JsonResponse
    {
        $this->assertCanAccess($procedure);
        $this->assertDraft($procedure);

        /** @var User $user */
        $user = $request->user();
        $file = $request->file('document');

        $path = $file->store("procedure_documents/{$procedure->id}", 'local');

        $nextVersion = (int) ProcedureDocument::withTrashed()
            ->where('procedure_id', $procedure->id)
            ->max('version') + 1;

        $document = ProcedureDocument::query()->create([
            'procedure_id' => $procedure->id,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'version' => max(1, $nextVersion),
            'uploaded_by' => $user->id,
        ]);

        $document->load('uploader:id,inn,email');

        return $this->created(
            new ProcedureDocumentResource($document),
            'Документ загружен.',
        );
    }

    /**
     * Скачивает файл документации (stream с закрытого диска local).
     *
     * @param Procedure $procedure Родительская ТЗП
     * @param int $document ID документа
     * @return StreamedResponse
     *
     * @throws AccessDeniedHttpException|NotFoundHttpException
     */
    public function download(Procedure $procedure, int $document): StreamedResponse
    {
        $this->assertCanAccess($procedure);

        $model = $this->findDocumentOrFail($procedure, $document);

        if (! Storage::disk('local')->exists($model->file_path)) {
            throw new NotFoundHttpException('Файл документа не найден на диске.');
        }

        return Storage::disk('local')->download($model->file_path, $model->file_name);
    }

    /**
     * Мягко удаляет документ (файл на диске сохраняем для аудита).
     *
     * @param Procedure $procedure Родительская ТЗП
     * @param int $document ID документа
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException|DomainException|NotFoundHttpException
     */
    public function destroy(Procedure $procedure, int $document): JsonResponse
    {
        $this->assertCanAccess($procedure);
        $this->assertDraft($procedure);

        $model = $this->findDocumentOrFail($procedure, $document);
        $model->delete();

        return $this->success(
            null,
            'Документ удалён.',
        );
    }

    /**
     * Находит документ процедуры.
     *
     * @param Procedure $procedure Родительская ТЗП
     * @param int $documentId ID документа
     * @return ProcedureDocument
     *
     * @throws NotFoundHttpException
     */
    private function findDocumentOrFail(Procedure $procedure, int $documentId): ProcedureDocument
    {
        $document = $procedure->documents()->whereKey($documentId)->first();

        if ($document === null) {
            throw new NotFoundHttpException('Документ не найден.');
        }

        return $document;
    }

    /**
     * Загрузку/удаление документов разрешаем только у черновика.
     *
     * @param Procedure $procedure Целевая ТЗП
     * @return void
     *
     * @throws DomainException
     */
    private function assertDraft(Procedure $procedure): void
    {
        if ($procedure->status !== ProcedureStatus::Draft) {
            throw new DomainException(
                message: 'Документы можно менять только у черновика процедуры.',
                statusCode: 422,
            );
        }
    }

    /**
     * trade_admin без super_admin — только свои ТЗП.
     *
     * @param Procedure $procedure Целевая ТЗП
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
}
