<?php

namespace Database\Factories;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Фабрика пользователей ЭТП (электронной торговой площадки).
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Текущий пароль, переиспользуемый между вызовами factory.
     *
     * @var string|null
     */
    protected static ?string $password;

    /**
     * Активный пользователь с подтверждённым email (happy path для тестов).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inn' => fake()->unique()->numerify('##########'),
            'email' => fake()->unique()->safeEmail(),
            'status' => UserStatus::Active,
            'email_verified_at' => now(),
            'approved_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'failed_login_attempts' => 0,
            'blocked_until' => null,
            'block_reason' => null,
        ];
    }

    /**
     * Email ещё не подтверждён (статус pending_email).
     *
     * @return static
     */
    public function pendingEmail(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::PendingEmail,
            'email_verified_at' => null,
            'approved_at' => null,
            'approved_by' => null,
        ]);
    }

    /**
     * Email подтверждён, ожидает одобрения администратором.
     *
     * @return static
     */
    public function pendingApproval(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::PendingApproval,
            'email_verified_at' => now(),
            'approved_at' => null,
            'approved_by' => null,
        ]);
    }

    /**
     * Пользователь заблокирован.
     *
     * @return static
     */
    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::Blocked,
            'blocked_until' => now()->addDays(7),
            'block_reason' => 'Нарушение регламента участия',
        ]);
    }

    /**
     * Email не подтверждён (статус может остаться active — для точечных кейсов).
     *
     * @return static
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
