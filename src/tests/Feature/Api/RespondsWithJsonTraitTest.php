<?php

namespace Tests\Feature\Api;

use App\Http\Controllers\Concerns\RespondsWithJson;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class RespondsWithJsonTraitTest extends TestCase
{
    use RespondsWithJson;

    /**
     * Check succes RespondsWithJson trait method.
     * Method must return response with code 200
     *
     * @return void
     */
    public function testRespondsWithJsonSuccessMethod()
    {
        // Arrange $ Act
        $response = new TestResponse($this->success(
            data: ['id' => 'test']
        ));

        // Assert
        $response->assertStatus(Response::HTTP_OK);

        $response->assertJson([
            'data' => ['id' => 'test'],
            'success' => true,
            'message' => null,
        ]);
    }

    /**
     * Check created RespondsWithJson trait method.
     * Method must return response with code 201
     *
     * @return void
     */
    public function testRespondsWithJsonCreatedMethod()
    {
        // Arrange $ Act
        $response = new TestResponse($this->created());

        // Assert
        $response->assertStatus(Response::HTTP_CREATED);

        $response->assertJson([
            "success" => true,
            "message" => null,
            "data" => null,
        ]);
    }

    /**
     * Check created RespondsWithJson trait method.
     * Method must return response with code 201
     *
     * @return void
     */
    public function testRespondsWithJsonCreatedNoContent()
    {
        // Arrange $ Act
        $response = new TestResponse($this->noContent());

        // Assert
        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $response->assertJson([]);
    }

    /**
     * Check error RespondsWithJson trait method.
     * Method must return response with code (4xx/5xx)
     *
     * @return void
     */
    public function testRespondsWithJsonCreatedError()
    {
        // Arrange $ Act
        $response = new TestResponse($this->error(
            message: "error message",
            status: Response::HTTP_BAD_REQUEST,
            errors: [],
        ));

        // Assert
        $response->assertStatus(Response::HTTP_BAD_REQUEST);

        $response->assertJson([
            "success" => false,
            "message" => "error message",
            "errors" => [],
        ]);
    }

    /**
     * Check paginated RespondsWithJson trait method.
     * Method must return response with laravel pagination structure.
     *
     * @return void
     */
    public function testRespondsWithJsonPaginated()
    {
        // Arrange: подготовка данных для пагинатора
        $items = [
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2'],
        ];

        $total = 25;          // Всего записей в БД
        $perPage = 10;        // На страницу
        $currentPage = 2;     // Текущая страница

        // Создаем экземпляр LengthAwarePaginator
        $paginator = new LengthAwarePaginator(
            items: $items,
            total: $total,
            perPage: $perPage,
            currentPage: $currentPage,
            options: [
                'path' => '/api/users', // Опционально: базовый путь для ссылок
            ]
        );

        // Act
        $response = new TestResponse($this->paginated(
            paginator: $paginator,
            message: "Users list",
        ));

        // Assert
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson([
            'success' => true,
            'message' => 'Users list',
            'data' => [
                ['id' => 1, 'name' => 'Item 1'],
                ['id' => 2, 'name' => 'Item 2'],
            ],
            'meta' => [
                'current_page' => 2,
                'per_page' => 10,
                'total' => 25,
                'last_page' => 3, // ceil(25 / 10) = 3
            ],
        ]);
    }

    /**
     * Check paginated RespondsWithJson trait method.
     * Method must return response with laravel pagination structure.
     *
     * @return void
     */
    public function testRespondsWithJsonValidationError()
    {
        // Arrange: подготовка данных для пагинатора
        $items = [
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2'],
        ];

        $total = 25;          // Всего записей в БД
        $perPage = 10;        // На страницу
        $currentPage = 2;     // Текущая страница

        // Создаем экземпляр LengthAwarePaginator
        $paginator = new LengthAwarePaginator(
            items: $items,
            total: $total,
            perPage: $perPage,
            currentPage: $currentPage,
            options: [
                'path' => '/api/users', // Опционально: базовый путь для ссылок
            ]
        );

        // Act
        $response = new TestResponse($this->paginated(
            paginator: $paginator,
            message: "Users list",
        ));

        // Assert
        $response->assertStatus(200);
    }
}
