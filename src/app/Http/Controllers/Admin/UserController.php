<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\BlockUserAction;
use App\Actions\Admin\UnblockUserAction;
use App\Contracts\UserRepositoryInterface;
use App\DTOs\BlockUserDTO;
use App\DTOs\UserFilterDTO;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\Admin\BlockUserRequest;
use App\Http\Requests\Api\Admin\ListUsersRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Админское управление пользователями ЭТП (электронной торговой площадки).
 *
 * Фаза 2.3–2.4: список, блокировка и разблокировка.
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
     * Действие блокировки учётной записи.
     *
     * @var BlockUserAction
     */
    private readonly BlockUserAction $blockUser;

    /**
     * Действие разблокировки учётной записи.
     *
     * @var UnblockUserAction
     */
    private readonly UnblockUserAction $unblockUser;

    /**
     * Создаёт контроллер управления пользователями.
     *
     * @param UserRepositoryInterface $users Репозиторий фильтрованного списка
     * @param BlockUserAction $blockUser Действие блокировки
     * @param UnblockUserAction $unblockUser Действие разблокировки
     * @return void
     */
    public function __construct(
        UserRepositoryInterface $users,
        BlockUserAction $blockUser,
        UnblockUserAction $unblockUser,
    ) {
        $this->users = $users;
        $this->blockUser = $blockUser;
        $this->unblockUser = $unblockUser;
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

    /**
     * Блокирует пользователя (статус blocked, причина, срок).
     *
     * Доступ: middleware `role:super_admin|trade_admin` (право users.block).
     *
     * @param BlockUserRequest $request Причина и опциональный blocked_until
     * @param User $user Цель блокировки
     * @return JsonResponse JSON с обновлённым пользователем
     */
    public function block(BlockUserRequest $request, User $user): JsonResponse
    {
        $blocked = $this->blockUser->execute(
            BlockUserDTO::fromRequest($user, $request->user(), $request),
        );

        return $this->success(
            new UserResource($blocked),
            'Пользователь заблокирован.',
        );
    }

    /**
     * Снимает блокировку и возвращает статус active.
     *
     * Доступ: middleware `role:super_admin|trade_admin` (право users.block).
     *
     * @param Request $request Аутентифицированный запрос администратора
     * @param User $user Заблокированный пользователь
     * @return JsonResponse JSON с обновлённым пользователем
     */
    public function unblock(Request $request, User $user): JsonResponse
    {
        $unblocked = $this->unblockUser->execute($user, $request->user());

        return $this->success(
            new UserResource($unblocked),
            'Пользователь разблокирован.',
        );
    }
}
