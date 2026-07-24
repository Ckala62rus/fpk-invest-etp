<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\UserRepositoryInterface;
use App\DTOs\UserFilterDTO;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\Admin\ListUsersRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * Админский CRUD-каркас пользователей ЭТП (электронной торговой площадки).
 *
 * Фаза 2.3: список с пагинацией и фильтрами (полный CRUD — в следующих пунктах фазы 2).
 */
class UserController extends ApiController
{
    /**
     * Репозиторий выборок пользователей.
     *
     * @var UserRepositoryInterface
     */
    private readonly UserRepositoryInterface $users;

    /**
     * Создаёт контроллер управления пользователями.
     *
     * @param UserRepositoryInterface $users Репозиторий фильтрованного списка
     * @return void
     */
    public function __construct(UserRepositoryInterface $users)
    {
        $this->users = $users;
    }

    /**
     * Возвращает постраничный список пользователей для администраторов и аудитора.
     *
     * Доступ: middleware `role:super_admin|trade_admin|auditor` (право users.view).
     *
     * @param ListUsersRequest $request Валидированные query-фильтры
     * @return JsonResponse Единый JSON с data + meta пагинации
     */
    public function index(ListUsersRequest $request): JsonResponse
    {
        $paginator = $this->users->paginate(
            UserFilterDTO::fromRequest($request),
        );

        $paginator->through(
            static fn (User $user): array => (new UserResource($user))->resolve(),
        );

        return $this->paginated($paginator, 'Список пользователей.');
    }
}
