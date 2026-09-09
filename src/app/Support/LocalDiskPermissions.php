<?php

namespace App\Support;

/**
 * Права на файлы диска local (storage/app/private) для PHP-FPM www-data.
 *
 * Нужен, если job когда-то отработал от root (старый Horizon) и создал каталоги 0700.
 */
final class LocalDiskPermissions
{
    /**
     * Делает файл и ближайшие родительские каталоги читаемыми/обходимыми для www-data.
     *
     * @param string $absolutePath Абсолютный путь к файлу на диске
     * @return void
     */
    public static function ensureWebReadable(string $absolutePath): void
    {
        $path = $absolutePath;

        if (is_file($path)) {
            @chmod($path, 0644);
        }

        for ($i = 0; $i < 4; $i++) {
            $path = dirname($path);
            if ($path === '' || $path === '/' || ! is_dir($path)) {
                break;
            }
            @chmod($path, 0755);
        }
    }
}
