<?php

namespace Tests\Feature\Api;

use App\Http\Controllers\ApiController;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Проверяет единый JSON-конверт trait {@see \App\Http\Controllers\Concerns\RespondsWithJson}
 * через тестовый контроллер.
 */
class RespondsWithJsonTraitTest extends TestCase
{
    /**
     * success() возвращает 200 и конверт с data.
     */
    public function test_success_returns_ok_with_data_envelope(): void
    {
        $response = $this->invokeControllerMethod(
            'success',
            [['id' => 'test']],
        );

        $response->assertOk();
        $response->assertExactJson([
            'success' => true,
            'message' => null,
            'data' => ['id' => 'test'],
        ]);
    }

    /**
     * success() принимает произвольное сообщение и HTTP-статус.
     */
    public function test_success_accepts_custom_message_and_status(): void
    {
        $response = $this->invokeControllerMethod(
            'success',
            [['accepted' => true], 'Операция выполнена', 202],
        );

        $response->assertStatus(Response::HTTP_ACCEPTED);
        $response->assertJson([
            'success' => true,
            'message' => 'Операция выполнена',
            'data' => ['accepted' => true],
        ]);
    }

    /**
     * created() возвращает 201 и тот же конверт, что и success().
     */
    public function test_created_returns_created_status(): void
    {
        $response = $this->invokeControllerMethod(
            'created',
            [['id' => 1], 'Ресурс создан'],
        );

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJson([
            'success' => true,
            'message' => 'Ресурс создан',
            'data' => ['id' => 1],
        ]);
    }

    /**
     * noContent() возвращает 204 без тела ответа.
     */
    public function test_no_content_returns_empty_body(): void
    {
        $response = $this->invokeControllerMethod('noContent');

        $response->assertNoContent();
    }

    /**
     * error() возвращает конверт ошибки с переданным статусом.
     */
    public function test_error_returns_error_envelope(): void
    {
        $response = $this->invokeControllerMethod(
            'error',
            ['Некорректный запрос', Response::HTTP_BAD_REQUEST, ['field' => ['Ошибка поля']]],
        );

        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response->assertExactJson([
            'success' => false,
            'message' => 'Некорректный запрос',
            'errors' => ['field' => ['Ошибка поля']],
        ]);
    }

    /**
     * paginated() возвращает data и meta пагинации.
     */
    public function test_paginated_returns_items_and_meta(): void
    {
        $paginator = $this->makePaginator(
            items: [
                ['id' => 1, 'name' => 'Item 1'],
                ['id' => 2, 'name' => 'Item 2'],
            ],
            total: 25,
            perPage: 10,
            currentPage: 2,
        );

        $response = $this->invokeControllerMethod(
            'paginated',
            [$paginator, 'Список пользователей'],
        );

        $response->assertOk();
        $response->assertExactJson([
            'success' => true,
            'message' => 'Список пользователей',
            'data' => [
                ['id' => 1, 'name' => 'Item 1'],
                ['id' => 2, 'name' => 'Item 2'],
            ],
            'meta' => [
                'current_page' => 2,
                'per_page' => 10,
                'total' => 25,
                'last_page' => 3,
            ],
        ]);
    }

    /**
     * validationError() возвращает 422 и errors по полям.
     */
    public function test_validation_error_returns_unprocessable_entity(): void
    {
        $response = $this->invokeControllerMethod(
            'validationError',
            [['inn' => ['ИНН уже зарегистрирован']], 'Ошибка валидации'],
        );

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertExactJson([
            'success' => false,
            'message' => 'Ошибка валидации',
            'errors' => ['inn' => ['ИНН уже зарегистрирован']],
        ]);
    }

    /**
     * validationError() использует сообщение по умолчанию.
     */
    public function test_validation_error_uses_default_message(): void
    {
        $response = $this->invokeControllerMethod(
            'validationError',
            [['email' => ['Некорректный email']]],
        );

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonPath('message', 'Validation failed');
    }

    /**
     * @return array<string, array{0: string, 1: int, 2: string}>
     */
    public static function authorizationErrorProvider(): array
    {
        return [
            'unauthorized' => ['unauthorized', Response::HTTP_UNAUTHORIZED, 'Unauthorized'],
            'forbidden' => ['forbidden', Response::HTTP_FORBIDDEN, 'Forbidden'],
            'not_found' => ['notFound', Response::HTTP_NOT_FOUND, 'Resource not found'],
        ];
    }

    /**
     * shortcut-методы unauthorized(), forbidden(), notFound() возвращают ожидаемые статусы.
     *
     * @param string $method
     * @param int $status
     * @param string $message
     */
    #[DataProvider('authorizationErrorProvider')]
    public function test_authorization_shortcuts_return_expected_status(
        string $method,
        int $status,
        string $message,
    ): void {
        $response = $this->invokeControllerMethod($method);

        $response->assertStatus($status);
        $response->assertExactJson([
            'success' => false,
            'message' => $message,
            'errors' => [],
        ]);
    }

    /**
     * Вызывает protected-метод trait через тестовый ApiController.
     *
     * @param string $method Имя метода trait (success, error, paginated, …)
     * @param array<int, mixed> $arguments Аргументы вызова
     * @return TestResponse
     */
    private function invokeControllerMethod(string $method, array $arguments = []): TestResponse
    {
        $controller = new class extends ApiController
        {
            /**
             * Проксирует вызов protected-метода trait для тестов.
             *
             * @param string $method Имя метода trait
             * @param array<int, mixed> $arguments Аргументы вызова
             * @return HttpResponse
             */
            public function call(string $method, array $arguments = []): HttpResponse
            {
                return $this->{$method}(...$arguments);
            }
        };

        return new TestResponse($controller->call($method, $arguments));
    }

    /**
     * Создаёт LengthAwarePaginator для тестов paginated().
     *
     * @param array<int, array<string, mixed>> $items Элементы текущей страницы
     * @param int $total Общее количество записей
     * @param int $perPage Размер страницы
     * @param int $currentPage Номер текущей страницы
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function makePaginator(
        array $items,
        int $total,
        int $perPage,
        int $currentPage,
    ): LengthAwarePaginator {
        return new LengthAwarePaginator(
            items: $items,
            total: $total,
            perPage: $perPage,
            currentPage: $currentPage,
            options: ['path' => '/api/users'],
        );
    }
}
