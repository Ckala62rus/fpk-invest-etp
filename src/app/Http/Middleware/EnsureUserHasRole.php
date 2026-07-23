<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Middleware проверки ролей RBAC (role-based access control) ЭТП (электронной торговой площадки).
 *
 * Разрешает доступ, если у аутентифицированного пользователя есть хотя бы одна
 * из перечисленных ролей. Параметры передаются через `|` или как отдельные аргументы:
 * `role:super_admin|trade_admin` или `role:super_admin,trade_admin`.
 */
class EnsureUserHasRole
{
    /**
     * Проверяет наличие требуемой роли у текущего пользователя.
     *
     * @param Request $request Входящий HTTP-запрос (ожидается auth:sanctum до этого middleware)
     * @param Closure(Request): Response $next Следующий обработчик конвейера
     * @param string ...$roles Имена ролей (через `|` внутри аргумента или отдельными параметрами)
     * @return Response Ответ следующего обработчика при успешной проверке
     *
     * @throws AccessDeniedHttpException Если пользователь не аутентифицирован или без нужной роли
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            throw new AccessDeniedHttpException('Требуется аутентификация.');
        }

        $allowedRoles = $this->normalizeRoles($roles);

        if ($allowedRoles === [] || !$user->hasAnyRole($allowedRoles)) {
            throw new AccessDeniedHttpException('Недостаточно прав для выполнения операции.');
        }

        return $next($request);
    }

    /**
     * Формирует строку middleware с перечислением допустимых ролей.
     *
     * Пример: `EnsureUserHasRole::using('super_admin', 'trade_admin')`.
     *
     * @param string ...$roles Имена ролей Spatie Permission
     * @return string Значение для метода Route::middleware()
     */
    public static function using(string ...$roles): string
    {
        return self::class.':'.implode('|', $roles);
    }

    /**
     * Нормализует список ролей из параметров middleware.
     *
     * @param array<int, string> $roles Сырые аргументы middleware
     * @return list<string> Уникальный список имён ролей без пустых значений
     */
    private function normalizeRoles(array $roles): array
    {
        $normalized = [];

        foreach ($roles as $roleArgument) {
            foreach (explode('|', $roleArgument) as $role) {
                $role = trim($role);

                if ($role === '') {
                    continue;
                }

                $normalized[] = $role;
            }
        }

        return array_values(array_unique($normalized));
    }
}
