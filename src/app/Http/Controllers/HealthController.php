<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Проверка живости API, БД и Redis (фаза 11.4).
 */
class HealthController extends ApiController
{
    /**
     * @return JsonResponse
     */
    public function __invoke(): JsonResponse
    {
        $db = false;
        $redis = false;

        try {
            DB::select('select 1');
            $db = true;
        } catch (Throwable) {
            $db = false;
        }

        try {
            Redis::connection()->ping();
            $redis = true;
        } catch (Throwable) {
            $redis = false;
        }

        $ok = $db;

        return response()->json([
            'success' => $ok,
            'message' => $ok ? 'Сервис доступен.' : 'База данных недоступна.',
            'data' => [
                'database' => $db,
                'redis' => $redis,
                'queue' => config('queue.default'),
                'broadcast' => config('broadcasting.default'),
            ],
        ], $ok ? 200 : 503);
    }
}
