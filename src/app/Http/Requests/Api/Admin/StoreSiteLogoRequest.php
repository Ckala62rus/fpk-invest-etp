<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Загрузка логотипа площадки (публичная шапка).
 */
class StoreSiteLogoRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'logo' => ['required', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'logo.required' => 'Выберите файл логотипа.',
            'logo.file' => 'Логотип должен быть файлом.',
            'logo.mimes' => 'Допустимые форматы логотипа: PNG, JPG, JPEG, WEBP, SVG.',
            'logo.max' => 'Размер логотипа не должен превышать 2 МБ.',
        ];
    }
}
