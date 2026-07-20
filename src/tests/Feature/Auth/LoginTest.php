<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithSanctumCsrf;
use Tests\TestCase;

/**
 * Проверяет вход пользователей в Sanctum SPA (Single Page Application) сессию.
 */
class LoginTest extends TestCase
{
    use InteractsWithSanctumCsrf;
    use RefreshDatabase;

    /**
     * Проверяет успешный вход активного пользователя.
     *
     * @return void
     */
    public function test_active_user_can_log_in(): void
    {
        $user = User::factory()->create();

        $this->withSanctumCsrf()
            ->postJson('/api/auth/login', ['inn' => $user->inn, 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user_id', $user->id);

        $this->assertAuthenticatedAs($user);
    }

    /**
     * Проверяет отказ при неверном пароле.
     *
     * @return void
     */
    public function test_login_rejects_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->withSanctumCsrf()
            ->postJson('/api/auth/login', ['inn' => $user->inn, 'password' => 'incorrect'])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Неверный ИНН или пароль');
    }

    /**
     * Проверяет ограничение пяти попыток входа в минуту.
     *
     * @return void
     */
    public function test_login_is_throttled_after_five_attempts(): void
    {
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->withSanctumCsrf()
                ->postJson('/api/auth/login', ['inn' => $user->inn, 'password' => "incorrect-{$attempt}"])
                ->assertUnauthorized();
        }

        $this->withSanctumCsrf()
            ->postJson('/api/auth/login', ['inn' => $user->inn, 'password' => 'incorrect'])
            ->assertStatus(429);
    }

    /**
     * Проверяет запрет входа до подтверждения email.
     *
     * @return void
     */
    public function test_pending_email_user_cannot_log_in(): void
    {
        $user = User::factory()->pendingEmail()->create();

        $this->withSanctumCsrf()
            ->postJson('/api/auth/login', ['inn' => $user->inn, 'password' => 'password'])
            ->assertForbidden();
    }

    /**
     * Проверяет запрет входа заблокированного пользователя.
     *
     * @return void
     */
    public function test_blocked_user_cannot_log_in(): void
    {
        $user = User::factory()->blocked()->create();

        $this->withSanctumCsrf()
            ->postJson('/api/auth/login', ['inn' => $user->inn, 'password' => 'password'])
            ->assertForbidden();
    }
}
