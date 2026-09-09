<?php

namespace App\Http\Controllers;

use App\Enums\ProposalStatus;
use App\Exceptions\DomainException;
use App\Http\Requests\Api\StoreProposalDocumentRequest;
use App\Http\Resources\ProposalDocumentResource;
use App\Models\Proposal;
use App\Models\ProposalDocument;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Документы коммерческого предложения (КП) участника.
 *
 * Фаза 6.2: загрузка на диск `local`, метаданные в `proposal_documents`.
 * Участник работает только со своей заявкой.
 */
class ProposalDocumentController extends ApiController
{
    /**
     * Список документов своей заявки.
     *
     * @param Proposal $proposal Заявка участника
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException
     */
    public function index(Proposal $proposal): JsonResponse
    {
        $this->assertOwnsProposal($proposal);

        $documents = $proposal->documents()->orderByDesc('id')->get();

        return $this->success(
            ProposalDocumentResource::collection($documents)->resolve(),
            'Документы коммерческого предложения.',
        );
    }

    /**
     * Загружает документ к своей заявке.
     *
     * @param StoreProposalDocumentRequest $request Файл document и опциональный type
     * @param Proposal $proposal Заявка участника
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException|DomainException
     */
    public function store(StoreProposalDocumentRequest $request, Proposal $proposal): JsonResponse
    {
        $this->assertOwnsProposal($proposal);
        $this->assertCanAttachDocuments($proposal);

        $file = $request->file('document');
        $path = $file->store("proposal_documents/{$proposal->id}", 'local');

        $document = ProposalDocument::query()->create([
            'proposal_id' => $proposal->id,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'type' => $request->validated('type'),
        ]);

        return $this->created(
            new ProposalDocumentResource($document),
            'Документ загружен.',
        );
    }

    /**
     * Скачивает файл документа своей заявки.
     *
     * @param Proposal $proposal Заявка участника
     * @param int $document ID документа
     * @return StreamedResponse
     *
     * @throws AccessDeniedHttpException|NotFoundHttpException
     */
    public function download(Proposal $proposal, int $document): StreamedResponse
    {
        $this->assertOwnsProposal($proposal);

        $model = $this->findDocumentOrFail($proposal, $document);

        if (! Storage::disk('local')->exists($model->file_path)) {
            throw new NotFoundHttpException('Файл документа не найден на диске.');
        }

        $inline = request()->boolean('inline');
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
     * @return string
     */
    private function safeFilename(string $fileName): string
    {
        return str_replace(['"', "\r", "\n"], '', $fileName);
    }

    /**
     * Удаляет документ своей заявки (файл с диска тоже).
     *
     * @param Proposal $proposal Заявка участника
     * @param int $document ID документа
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException|DomainException|NotFoundHttpException
     */
    public function destroy(Proposal $proposal, int $document): JsonResponse
    {
        $this->assertOwnsProposal($proposal);
        $this->assertCanAttachDocuments($proposal);

        $model = $this->findDocumentOrFail($proposal, $document);

        if (Storage::disk('local')->exists($model->file_path)) {
            Storage::disk('local')->delete($model->file_path);
        }

        $model->delete();

        return $this->success(null, 'Документ удалён.');
    }

    /**
     * Участник может менять документы только до завершения рассмотрения.
     *
     * @param Proposal $proposal Целевая заявка
     * @return void
     *
     * @throws DomainException
     */
    private function assertCanAttachDocuments(Proposal $proposal): void
    {
        $editable = in_array($proposal->status, [
            ProposalStatus::Draft,
            ProposalStatus::Submitted,
            ProposalStatus::Clarification,
        ], true);

        if (! $editable) {
            throw new DomainException(
                message: 'Документы нельзя изменить на текущем статусе заявки.',
                statusCode: 422,
            );
        }
    }

    /**
     * Проверяет, что текущий пользователь — владелец заявки.
     *
     * @param Proposal $proposal Целевая заявка
     * @return void
     *
     * @throws AccessDeniedHttpException
     */
    private function assertOwnsProposal(Proposal $proposal): void
    {
        /** @var User|null $user */
        $user = auth()->user();

        if ($user === null || (int) $proposal->user_id !== (int) $user->id) {
            throw new AccessDeniedHttpException('Доступны только документы своей заявки.');
        }
    }

    /**
     * Находит документ заявки или 404.
     *
     * @param Proposal $proposal Родительская заявка
     * @param int $documentId ID документа
     * @return ProposalDocument
     *
     * @throws NotFoundHttpException
     */
    private function findDocumentOrFail(Proposal $proposal, int $documentId): ProposalDocument
    {
        $model = $proposal->documents()->whereKey($documentId)->first();

        if ($model === null) {
            throw new NotFoundHttpException('Документ заявки не найден.');
        }

        return $model;
    }
}
