<?php

namespace App\Support\Api;

use Illuminate\Http\JsonResponse;

/**
 * Фабрика единого JSON-конверта API-ответов (успех и ошибки).
 */
class ApiJsonResponse
{
    /**
     * Ответ с ошибкой в формате API.
     *
     * @param string $message Человекочитаемое сообщение
     * @param int $status HTTP-статус (4xx/5xx)
     * @param array<string, array<int, string>|string> $errors Ошибки по полям
     * @return JsonResponse
     */
    public static function error(
        string $message,
        int $status = 400,
        array $errors = [],
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
