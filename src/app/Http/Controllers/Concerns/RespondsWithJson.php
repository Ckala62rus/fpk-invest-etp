<?php

namespace App\Http\Controllers\Concerns;

use App\Support\Api\ApiJsonResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;

trait RespondsWithJson
{
    /**
     * Успешный ответ (200).
     *
     * @param mixed $data
     * @param string|null $message
     * @param int $status
     * @return JsonResponse
     */
    protected function success(
        mixed $data = null,
        ?string $message = null,
        int $status = 200
    ): JsonResponse {
        $payload = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        return response()->json($payload, $status);
    }

    /**
     * Успешное создание ресурса (201).
     *
     * @param mixed $data
     * @param string|null $message
     * @return JsonResponse
     */
    protected function created(
        mixed $data = null,
        ?string $message = null
    ): JsonResponse {
        return $this->success($data, $message, 201);
    }

    /**
     * Успешный ответ без содержимого (204).
     *
     * @return Response
     */
    protected function noContent(): Response
    {
        return response()->noContent();
    }

    /**
     * Ответ с ошибкой (4xx/5xx).
     *
     * @param string $message
     * @param int $status
     * @param array $errors
     * @return JsonResponse
     */
    protected function error(
        string $message,
        int $status = 400,
        array $errors = []
    ): JsonResponse {
        return ApiJsonResponse::error($message, $status, $errors);
    }

    /**
     * Ответ со списком и пагинацией.
     *
     * @param LengthAwarePaginator $paginator
     * @param string|null $message
     * @return JsonResponse
     */
    protected function paginated(
        LengthAwarePaginator $paginator,
        ?string $message = null
    ): JsonResponse {
        $payload = [
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ];

        return response()->json($payload, 200);
    }

    /**
     * Ответ с ошибкой валидации (422).
     *
     * @param array $errors
     * @param string $message
     * @return JsonResponse
     */
    protected function validationError(
        array $errors,
        string $message = 'Validation failed'
    ): JsonResponse {
        return $this->error($message, 422, $errors);
    }

    /**
     * Ответ с ошибкой "Не найдено" (404).
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function notFound(
        string $message = 'Resource not found'
    ): JsonResponse {
        return $this->error($message, 404);
    }

    /**
     * Ответ с ошибкой "Запрещено" (403).
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function forbidden(
        string $message = 'Forbidden'
    ): JsonResponse {
        return $this->error($message, 403);
    }

    /**
     * Ответ с ошибкой "Не авторизован" (401).
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function unauthorized(
        string $message = 'Unauthorized'
    ): JsonResponse {
        return $this->error($message, 401);
    }
}