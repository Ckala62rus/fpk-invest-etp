<?php

namespace Tests\Concerns;

/**
 * Получает CSRF-cookie для тестов браузерной Sanctum SPA (Single Page Application) сессии.
 */
trait InteractsWithSanctumCsrf
{
    /**
     * Запрашивает CSRF-cookie с Origin фронтенда и возвращает тест для цепочки запросов.
     *
     * Cookie обязателен: Sanctum защищает stateful SPA-запросы от CSRF (Cross-Site Request Forgery).
     *
     * @return static
     */
    protected function withSanctumCsrf(): static
    {
        $this->withHeader('Origin', 'http://localhost:5173')
            ->get('/sanctum/csrf-cookie')
            ->assertNoContent();

        return $this;
    }
}
