<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс конкурсного документа ТЗП для админского API.
 *
 * @mixin \App\Models\ProcedureDocument
 */
class ProcedureDocumentResource extends JsonResource
{
    /**
     * Преобразует документ процедуры в JSON (без отдачи бинарного содержимого).
     *
     * @param Request $request Текущий HTTP-запрос
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'procedure_id' => $this->procedure_id,
            'file_name' => $this->file_name,
            'file_path' => $this->file_path,
            'version' => $this->version,
            'uploaded_by' => $this->uploaded_by,
            'uploader' => $this->whenLoaded('uploader', function () {
                return [
                    'id' => $this->uploader->id,
                    'inn' => $this->uploader->inn,
                    'email' => $this->uploader->email,
                ];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
