<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\ApproveUserAction;
use App\DTOs\ApproveUserDTO;
use App\Http\Controllers\ApiController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Контроллер одобрения зарегистрированных пользователей администрацией ЭТП.
 *
 * Доступ к маршруту ограничен middleware `role:super_admin|trade_admin`.
 */
class UserApprovalController extends ApiController
{
    /**
     * Действие активации учётной записи.
     *
     * @var ApproveUserAction
     */
    private readonly ApproveUserAction $approveUser;

    /**
     * Создаёт контроллер одобрения пользователей.
     *
     * @param ApproveUserAction $approveUser Действие одобрения пользователя
     * @return void
     */
    public function __construct(ApproveUserAction $approveUser)
    {
        $this->approveUser = $approveUser;
    }

    /**
     * Одобряет пользователя от имени главного администратора или администратора торгов.
     *
     * @param Request $request Аутентифицированный HTTP-запрос администратора
     * @param User $user Пользователь для активации
     * @return JsonResponse Единый JSON-ответ с активированным пользователем
     */
    public function store(Request $request, User $user): JsonResponse
    {
        $user = $this->approveUser->execute(
            ApproveUserDTO::fromUsers($user, $request->user()),
        );

        return $this->success(
            ['user_id' => $user->id, 'status' => $user->status->value],
            'Пользователь одобрен.',
        );
    }
}
