<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\StoreUserDocumentRequest;
use App\Models\UserDocument;
use Illuminate\Http\JsonResponse;

/**
 * Контроллер загрузки собственных документов пользователя ЭТП (электронной торговой площадки).
 */
class UserDocumentController extends ApiController
{
    /**
     * Сохраняет документ текущего пользователя на локальном закрытом диске.
     *
     * @param StoreUserDocumentRequest $request Проверенный запрос с файлом документа
     * @return JsonResponse Единый JSON-ответ с метаданными документа
     */
    public function store(StoreUserDocumentRequest $request): JsonResponse
    {
        $file = $request->file('document');
        $user = $request->user();
        $path = $file->store("user_documents/{$user->id}", 'local');

        $document = UserDocument::query()->create([
            'user_id' => $user->id,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'valid_until' => now()->addYear(),
            'uploaded_at' => now(),
        ]);

        return $this->created(
            ['id' => $document->id, 'file_name' => $document->file_name],
            'Документ загружен.',
        );
    }
}
