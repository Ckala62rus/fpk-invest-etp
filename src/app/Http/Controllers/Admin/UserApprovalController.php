<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\ApproveUserAction;
use App\Http\Controllers\ApiController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Контроллер одобрения зарегистрированных пользователей администрацией ЭТП.
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
        $administrator = $request->user();

        if (!$administrator->hasAnyRole(['super_admin', 'trade_admin'])) {
            throw new AccessDeniedHttpException('Недостаточно прав для одобрения пользователя.');
        }

        $user = $this->approveUser->execute($user, $administrator);

        return $this->success(
            ['user_id' => $user->id, 'status' => $user->status->value],
            'Пользователь одобрен.',
        );
    }
}
