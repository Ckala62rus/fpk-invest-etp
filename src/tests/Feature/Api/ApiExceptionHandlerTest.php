<?php

namespace Tests\Feature\Api;

use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Проверяет единый JSON-конверт при HTTP-исключениях на api/*.
 */
class ApiExceptionHandlerTest extends TestCase
{
    /**
     * ValidationException отдаёт 422 и errors по полям.
     */
    public function test_validation_exception_returns_json_envelope(): void
    {
        $response = $this->getJson('/api/_test/exceptions/422');

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertExactJson([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => [
                'email' => ['Email is required'],
            ],
        ]);
    }

    /**
     * AuthenticationException отдаёт 401 в формате API.
     */
    public function test_authentication_exception_returns_json_envelope(): void
    {
        $response = $this->getJson('/api/_test/exceptions/401');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
        $response->assertExactJson([
            'success' => false,
            'message' => 'Unauthorized',
            'errors' => [],
        ]);
    }

    /**
     * AuthorizationException отдаёт 403 в формате API.
     */
    public function test_authorization_exception_returns_json_envelope(): void
    {
        $response = $this->getJson('/api/_test/exceptions/403');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
        $response->assertExactJson([
            'success' => false,
            'message' => 'Forbidden',
            'errors' => [],
        ]);
    }

    /**
     * NotFoundHttpException отдаёт 404 в формате API.
     */
    public function test_not_found_http_exception_returns_json_envelope(): void
    {
        $response = $this->getJson('/api/_test/exceptions/404');

        $response->assertStatus(Response::HTTP_NOT_FOUND);
        $response->assertExactJson([
            'success' => false,
            'message' => 'Resource not found',
            'errors' => [],
        ]);
    }

    /**
     * ModelNotFoundException отдаёт 404 в формате API.
     */
    public function test_model_not_found_exception_returns_json_envelope(): void
    {
        $response = $this->getJson('/api/_test/exceptions/404-model');

        $response->assertStatus(Response::HTTP_NOT_FOUND);
        $response->assertExactJson([
            'success' => false,
            'message' => 'Resource not found',
            'errors' => [],
        ]);
    }

    /**
     * DomainException отдаёт заданный статус и errors.
     */
    public function test_domain_exception_returns_json_envelope(): void
    {
        $response = $this->getJson('/api/_test/exceptions/409');

        $response->assertStatus(Response::HTTP_CONFLICT);
        $response->assertExactJson([
            'success' => false,
            'message' => 'Bid too low',
            'errors' => [
                'amount' => ['Ставка ниже минимального шага'],
            ],
        ]);
    }
}
