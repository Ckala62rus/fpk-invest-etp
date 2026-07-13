<?php

use App\Http\Controllers\Api\TestController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/test', function (): JsonResponse {
    $message = 'ETP API работает';

    return response()->json([
        'status' => 'ok',
        'message' => $message,
        'timestamp' => now()->toIso8601String(),
        'environment' => app()->environment(),
    ]);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
