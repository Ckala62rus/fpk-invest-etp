<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Запрос загрузки конкурсной документации ТЗП.
 */
class StoreProcedureDocumentRequest extends FormRequest
{
    /**
     * Доступ контролируется middleware ролей.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила загрузки файла документации.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'document' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,zip', 'max:20480'],
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
            'document.required' => 'Прикрепите файл документации.',
            'document.file' => 'Документация должна быть файлом.',
            'document.mimes' => 'Допустимые форматы: pdf, doc, docx, xls, xlsx, zip.',
            'document.max' => 'Размер файла не должен превышать 20 МБ.',
        ];
    }
}
