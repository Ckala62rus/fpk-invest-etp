<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Запрос загрузки документа к коммерческому предложению (КП).
 */
class StoreProposalDocumentRequest extends FormRequest
{
    /**
     * Доступ контролируется middleware ролей и владельцем заявки.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила загрузки файла КП.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'document' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,zip', 'max:20480'],
            'type' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Русские сообщения об ошибках валидации.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'document.required' => 'Прикрепите файл документа.',
            'document.file' => 'Документ должен быть файлом.',
            'document.mimes' => 'Допустимые форматы: pdf, doc, docx, xls, xlsx, zip.',
            'document.max' => 'Размер файла не должен превышать 20 МБ.',
            'type.string' => 'Тип документа должен быть строкой.',
            'type.max' => 'Тип документа не должен превышать :max символов.',
        ];
    }
}
