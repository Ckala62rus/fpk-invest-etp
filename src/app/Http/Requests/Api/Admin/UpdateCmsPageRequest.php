<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Запрос обновления страницы CMS (только super_admin).
 *
 * При передаче content_html создаётся новая ревизия (история версий).
 */
class UpdateCmsPageRequest extends FormRequest
{
    /**
     * Доступ контролируется middleware `role:super_admin`.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила обновления страницы CMS.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $pageId = $this->route('cmsPage');

        return [
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('cms_pages', 'slug')->ignore($pageId),
            ],
            'title' => ['sometimes', 'string', 'max:255'],
            'meta_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'meta_description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'is_published' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'content_html' => ['sometimes', 'string'],
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
            'slug.string' => 'Slug страницы должен быть строкой.',
            'slug.max' => 'Slug страницы не должен превышать :max символов.',
            'slug.regex' => 'Slug может содержать только латинские буквы в нижнем регистре, цифры и дефисы.',
            'slug.unique' => 'Страница с таким slug уже существует.',
            'title.string' => 'Заголовок страницы должен быть строкой.',
            'title.max' => 'Заголовок страницы не должен превышать :max символов.',
            'meta_title.string' => 'Meta title должен быть строкой.',
            'meta_title.max' => 'Meta title не должен превышать :max символов.',
            'meta_description.string' => 'Meta description должен быть строкой.',
            'meta_description.max' => 'Meta description не должен превышать :max символов.',
            'is_published.boolean' => 'Поле публикации должно быть логическим значением.',
            'sort_order.integer' => 'Порядок сортировки должен быть целым числом.',
            'sort_order.min' => 'Порядок сортировки не может быть отрицательным.',
            'content_html.string' => 'Содержимое страницы должно быть строкой.',
        ];
    }
}
