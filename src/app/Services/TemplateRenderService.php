<?php

namespace App\Services;

/**
 * Подстановка плейсхолдеров в subject/body шаблонов уведомлений (фаза 7.2).
 *
 * Формат: {{procedure.number}}, {{user.email}} — dot-notation по массиву данных.
 */
class TemplateRenderService
{
    /**
     * Заменяет плейсхолдеры {{key}} на значения из $data.
     *
     * @param string $template Текст с плейсхолдерами
     * @param array<string, mixed> $data Данные для подстановки
     * @return string
     */
    public function render(string $template, array $data): string
    {
        return (string) preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/',
            function (array $matches) use ($data): string {
                $value = data_get($data, $matches[1]);

                if ($value === null) {
                    return '';
                }

                if (is_bool($value)) {
                    return $value ? '1' : '0';
                }

                if (is_scalar($value)) {
                    return (string) $value;
                }

                return '';
            },
            $template,
        );
    }
}
