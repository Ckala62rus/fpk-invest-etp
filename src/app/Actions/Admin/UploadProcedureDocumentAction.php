<?php

namespace App\Actions\Admin;

use App\Enums\ApprovalStatus;
use App\Enums\ProcedureStatus;
use App\Exceptions\DomainException;
use App\Models\Procedure;
use App\Models\ProcedureChangeLog;
use App\Models\ProcedureDocument;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Загрузка конкурсной документации ТЗП (черновик или опубликованная процедура).
 *
 * Фаза 6.6 + 6.7: для accepting — проверка срока редактирования и pending change log.
 */
class UploadProcedureDocumentAction
{
    /**
     * @param SettingsService $settings Глобальные настройки
     * @return void
     */
    public function __construct(
        private readonly SettingsService $settings,
    ) {
    }

    /**
     * Загружает документ процедуры.
     *
     * @param Procedure $procedure ТЗП
     * @param UploadedFile $file Файл
     * @param User $uploader Администратор
     * @return ProcedureDocument Созданная запись
     *
     * @throws DomainException
     */
    public function execute(Procedure $procedure, UploadedFile $file, User $uploader): ProcedureDocument
    {
        if ($procedure->status === ProcedureStatus::Draft) {
            return $this->uploadDraftDocument($procedure, $file, $uploader);
        }

        if ($procedure->status === ProcedureStatus::Accepting) {
            return $this->uploadPublishedDocument($procedure, $file, $uploader);
        }

        throw new DomainException(
            message: 'Документы можно менять только у черновика или процедуры в приёме заявок.',
            statusCode: 422,
        );
    }

    /**
     * @param Procedure $procedure Черновик
     * @param UploadedFile $file Файл
     * @param User $uploader Администратор
     * @return ProcedureDocument
     */
    private function uploadDraftDocument(
        Procedure $procedure,
        UploadedFile $file,
        User $uploader,
    ): ProcedureDocument {
        return DB::transaction(function () use ($procedure, $file, $uploader): ProcedureDocument {
            $path = $file->store("procedure_documents/{$procedure->id}", 'local');
            $nextVersion = (int) ProcedureDocument::withTrashed()
                ->where('procedure_id', $procedure->id)
                ->max('version') + 1;

            return ProcedureDocument::query()->create([
                'procedure_id' => $procedure->id,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'version' => max(1, $nextVersion),
                'uploaded_by' => $uploader->id,
            ]);
        });
    }

    /**
     * @param Procedure $procedure Опубликованная ТЗП
     * @param UploadedFile $file Файл
     * @param User $uploader Администратор
     * @return ProcedureDocument
     *
     * @throws DomainException
     */
    private function uploadPublishedDocument(
        Procedure $procedure,
        UploadedFile $file,
        User $uploader,
    ): ProcedureDocument {
        if (! $this->settings->canEditProcedureDocuments($procedure)) {
            $days = $this->settings->getInt(SettingsService::DOC_EDIT_DEADLINE_DAYS, 2);

            throw new DomainException(
                message: "Редактирование документации запрещено менее чем за {$days} дн. до окончания приёма.",
                statusCode: 422,
            );
        }

        if (
            ProcedureChangeLog::query()
                ->where('procedure_id', $procedure->id)
                ->where('approval_status', ApprovalStatus::Pending)
                ->exists()
        ) {
            throw new DomainException(
                message: 'Уже есть изменение документации, ожидающее согласования.',
                statusCode: 422,
            );
        }

        return DB::transaction(function () use ($procedure, $file, $uploader): ProcedureDocument {
            $path = $file->store("procedure_documents/{$procedure->id}", 'local');
            $nextVersion = (int) ProcedureDocument::withTrashed()
                ->where('procedure_id', $procedure->id)
                ->max('version') + 1;

            $document = ProcedureDocument::query()->create([
                'procedure_id' => $procedure->id,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'version' => max(1, $nextVersion),
                'uploaded_by' => $uploader->id,
            ]);

            ProcedureChangeLog::query()->create([
                'procedure_id' => $procedure->id,
                'changed_by' => $uploader->id,
                'change_summary' => 'Загрузка новой версии документации: '.$document->file_name,
                'diff' => [
                    'document_id' => $document->id,
                    'file_name' => $document->file_name,
                    'version' => $document->version,
                ],
                'approval_status' => ApprovalStatus::Pending,
            ]);

            return $document;
        });
    }
}
