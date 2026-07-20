<?php

namespace App\Http\Controllers;

use App\Models\UserDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Контроллер загрузки собственных документов пользователя ЭТП (электронной торговой площадки).
 */
class UserDocumentController extends ApiController
{
    /**
     * Сохраняет документ текущего пользователя на локальном закрытом диске.
     *
     * @param Request $request Аутентифицированный HTTP-запрос с файлом документа
     * @return JsonResponse Единый JSON-ответ с метаданными документа
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'document' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx', 'max:10240'],
        ]);
        $file = $data['document'];
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
