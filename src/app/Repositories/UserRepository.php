<?php

namespace App\Repositories;

use App\Contracts\UserRepositoryInterface;
use App\DTOs\UserFilterDTO;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Eloquent-репозиторий пользователей ЭТП (электронной торговой площадки).
 *
 * Инкапсулирует фильтры списка для админки: статус, поиск, роль, soft delete.
 */
class UserRepository implements UserRepositoryInterface
{
    /**
     * Возвращает постраничный список пользователей с фильтрами.
     *
     * @param UserFilterDTO $filter Фильтры статуса, поиска, роли и soft delete
     * @return LengthAwarePaginator Пагинатор моделей User
     */
    public function paginate(UserFilterDTO $filter): LengthAwarePaginator
    {
        $query = User::query()
            ->with(['roles', 'profile'])
            ->orderByDesc('id');

        $this->applyTrashed($query, $filter->trashed);
        $this->applyStatus($query, $filter);
        $this->applySearch($query, $filter);
        $this->applyRole($query, $filter);

        return $query->paginate($filter->perPage);
    }

    /**
     * Применяет режим учёта soft-deleted записей.
     *
     * @param Builder<User> $query Базовый запрос пользователей
     * @param string|null $trashed without|with|only
     * @return void
     */
    private function applyTrashed(Builder $query, ?string $trashed): void
    {
        match ($trashed) {
            'with' => $query->withTrashed(),
            'only' => $query->onlyTrashed(),
            default => null,
        };
    }

    /**
     * Фильтрует пользователей по статусу учётной записи.
     *
     * @param Builder<User> $query Базовый запрос пользователей
     * @param UserFilterDTO $filter DTO фильтров
     * @return void
     */
    private function applyStatus(Builder $query, UserFilterDTO $filter): void
    {
        if ($filter->status === null) {
            return;
        }

        $query->where('status', $filter->status);
    }

    /**
     * Ищет по ИНН (идентификационный номер налогоплательщика), email и наименованию профиля.
     *
     * @param Builder<User> $query Базовый запрос пользователей
     * @param UserFilterDTO $filter DTO фильтров
     * @return void
     */
    private function applySearch(Builder $query, UserFilterDTO $filter): void
    {
        $search = $filter->search;

        if ($search === null || trim($search) === '') {
            return;
        }

        $term = '%'.trim($search).'%';

        $query->where(function (Builder $builder) use ($term): void {
            $builder
                ->where('inn', 'ilike', $term)
                ->orWhere('email', 'ilike', $term)
                ->orWhereHas('profile', function (Builder $profileQuery) use ($term): void {
                    $profileQuery->where('name', 'ilike', $term);
                });
        });
    }

    /**
     * Оставляет пользователей с указанной ролью RBAC (role-based access control).
     *
     * @param Builder<User> $query Базовый запрос пользователей
     * @param UserFilterDTO $filter DTO фильтров
     * @return void
     */
    private function applyRole(Builder $query, UserFilterDTO $filter): void
    {
        if ($filter->role === null || trim($filter->role) === '') {
            return;
        }

        $query->role($filter->role);
    }
}
