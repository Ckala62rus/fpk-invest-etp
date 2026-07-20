<?php

namespace Tests\Feature\Auth;

use App\Enums\UserStatus;
use App\Mail\VerifyEmailMail;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Проверяет публичную регистрацию участника ЭТП (электронной торговой площадки).
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Подготавливает системные роли для назначения участнику.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * Проверяет создание учётной записи, профиля и письма подтверждения.
     *
     * @return void
     */
    public function test_user_can_register(): void
    {
        Mail::fake();

        $this->postJson('/api/auth/register', $this->registrationData())
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', UserStatus::PendingEmail->value);

        $user = User::query()->where('inn', '7701234567')->firstOrFail();
        $this->assertDatabaseHas('user_profiles', ['user_id' => $user->id]);
        $this->assertDatabaseHas('user_notification_settings', ['user_id' => $user->id]);
        $this->assertTrue($user->hasRole('participant'));
        Mail::assertSent(VerifyEmailMail::class);
    }

    /**
     * Проверяет запрет повторного использования ИНН.
     *
     * @return void
     */
    public function test_registration_rejects_duplicate_inn(): void
    {
        User::factory()->create(['inn' => '7701234567']);

        $this->postJson('/api/auth/register', $this->registrationData())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('inn');
    }

    /**
     * Возвращает корректные данные регистрации.
     *
     * @return array<string, mixed>
     */
    private function registrationData(): array
    {
        return [
            'inn' => '7701234567',
            'email' => 'participant@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'entity_type' => 'legal',
            'name' => 'ООО Тест',
            'phone' => '+79990000000',
            'director_name' => 'Иванов Иван Иванович',
            'director_birth_date' => '1980-01-01',
            'contact_persons' => 'Петров Пётр Петрович',
            'extra_emails' => ['other@example.test'],
            'pd_consent' => true,
        ];
    }
}
