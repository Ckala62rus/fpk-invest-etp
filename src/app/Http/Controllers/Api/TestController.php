<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Тестовый контроллер для проверки CORS и Sanctum SPA (только local/testing routes).
 */
class TestController extends ApiController
{
    /**
     * Возвращает данные сессии и Origin для smoke-тестов CORS.
     *
     * @param Request $request HTTP-запрос
     * @return JsonResponse
     */
    public function testCors(Request $request): JsonResponse
    {
        return $this->success([
            'session_id' => session()->getId(),
            'origin' => $request->header('Origin'),
        ], 'CORS работает!');
    }
}
