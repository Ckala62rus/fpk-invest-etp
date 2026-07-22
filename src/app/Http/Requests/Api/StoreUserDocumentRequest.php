<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Запрос загрузки учредительного или регистрационного документа пользователя.
 */
class StoreUserDocumentRequest extends FormRequest
{
    /**
     * Загружать документы может только аутентифицированный пользователь.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'document' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx', 'max:10240'],
        ];
    }

    /**
     * Русские сообщения об ошибках валидации для фронтенда.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'document.required' => 'Прикрепите файл документа.',
            'document.file' => 'Документ должен быть файлом.',
            'document.mimes' => 'Допустимые форматы: pdf, doc, docx, xls, xlsx.',
            'document.max' => 'Размер файла не должен превышать 10 МБ.',
        ];
    }
}
