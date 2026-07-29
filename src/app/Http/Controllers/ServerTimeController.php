<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * Endpoint времени сервера для синхронизации часов на SPA ЭТП.
 *
 * Фаза 4.4: фронт показывает единое серверное время (дедлайны аукционов/приёма заявок).
 * Доступен без авторизации.
 */
class ServerTimeController extends ApiController
{
    /**
     * Возвращает текущее время сервера в ISO-8601 и Unix timestamp.
     *
     * @return JsonResponse
     */
    public function __invoke(): JsonResponse
    {
        $now = now();

        return $this->success(
            [
                'datetime' => $now->toIso8601String(),
                'timestamp' => $now->getTimestamp(),
                'timezone' => (string) config('app.timezone'),
            ],
            'Время сервера.',
        );
    }
}
