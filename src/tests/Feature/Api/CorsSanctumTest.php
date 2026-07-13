<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

/**
 * Проверяет CORS и Sanctum SPA cookie-auth для Vue dev server.
 */
class CorsSanctumTest extends TestCase
{
    /**
     * GET с Origin возвращает CORS-заголовки и credentials.
     */
    public function test_cors_headers_with_origin(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'http://localhost:5173',
        ])->get('/api/_test/cors');

        $response->assertOk();
        $response->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');
        $response->assertHeader('Access-Control-Allow-Credentials', 'true');
        $this->assertNotEmpty(session()->getId());
    }

    /**
     * /sanctum/csrf-cookie устанавливает XSRF-TOKEN.
     */
    public function test_sanctum_csrf_cookie(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'http://localhost:5173',
        ])->get('/sanctum/csrf-cookie');

        $response->assertNoContent();

        $cookies = $response->headers->all('set-cookie');
        $cookieHeader = implode('; ', $cookies);

        $this->assertStringContainsString('XSRF-TOKEN', $cookieHeader);
    }

    /**
     * После CSRF-cookie API-запрос сохраняет сессию.
     */
    public function test_api_request_with_session(): void
    {
        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
        ])->get('/sanctum/csrf-cookie');

        $response = $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->get('/api/_test/cors');

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'CORS работает!',
        ]);
        $response->assertJsonStructure([
            'data' => ['session_id', 'origin'],
        ]);
        $this->assertNotEmpty($response->json('data.session_id'));
    }

    /**
     * Preflight OPTIONS возвращает CORS-заголовки.
     */
    public function test_preflight_request(): void
    {
        $response = $this->call('OPTIONS', '/api/_test/cors', [], [], [], [
            'HTTP_ORIGIN' => 'http://localhost:5173',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'X-Requested-With, Content-Type',
        ]);

        $response->assertNoContent();
        $response->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');
        $response->assertHeader('Access-Control-Allow-Credentials', 'true');
        $response->assertHeader('Access-Control-Allow-Methods');
        $response->assertHeader('Access-Control-Allow-Headers');
    }
}
