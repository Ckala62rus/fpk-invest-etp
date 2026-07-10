<?php

use App\Exceptions\DomainException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Тестовые маршруты для проверки JSON-формата исключений (только local/testing).
 */
Route::prefix('/api/_test/exceptions')->group(function () {
    Route::get('/422', function () {
        throw ValidationException::withMessages([
            'email' => ['Email is required'],
        ]);
    });

    Route::get('/401', function () {
        throw new AuthenticationException('Unauthenticated');
    });

    Route::get('/403', function () {
        throw new AccessDeniedHttpException('Forbidden');
    });

    Route::get('/404', function () {
        throw new NotFoundHttpException();
    });

    Route::get('/404-model', function () {
        throw new ModelNotFoundException();
    });

    Route::get('/409', function () {
        throw new DomainException(
            'Bid too low',
            409,
            ['amount' => ['Ставка ниже минимального шага']],
        );
    });
});
