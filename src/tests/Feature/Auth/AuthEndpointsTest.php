<?php

namespace Tests\Feature\Auth;

use App\Enums\UserStatus;
use App\Mail\PasswordResetMail;
use App\Models\PasswordResetToken;
use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Проверяет остальные конечные точки первой фазы аутентификации ЭТП.
 */
class AuthEndpointsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Подготавливает роли для проверок прав доступа.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * Проверяет подтверждение email по подписанной ссылке.
     *
     * @return void
     */
    public function test_signed_email_link_verifies_user(): void
    {
        $user = User::factory()->pendingEmail()->create();
        $url = URL::temporarySignedRoute(
            'auth.email.verify',
            now()->addMinutes(10),
            ['user' => $user->id, 'hash' => sha1($user->email)],
        );

        $this->getJson($url)->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'status' => UserStatus::PendingApproval->value,
        ]);
    }

    /**
     * Проверяет запрет неподписанной ссылки подтверждения email.
     *
     * @return void
     */
    public function test_invalid_email_link_is_rejected(): void
    {
        $user = User::factory()->pendingEmail()->create();

        $this->getJson("/api/auth/email/verify/{$user->id}/".sha1($user->email))
            ->assertForbidden();
    }

    /**
     * Проверяет запрет просроченной подписанной ссылки подтверждения email.
     *
     * @return void
     */
    public function test_expired_email_link_is_rejected(): void
    {
        $user = User::factory()->pendingEmail()->create();
        $url = URL::temporarySignedRoute(
            'auth.email.verify',
            now()->subMinute(),
            ['user' => $user->id, 'hash' => sha1($user->email)],
        );

        $this->getJson($url)->assertForbidden();
    }

    /**
     * Проверяет, что участник не может одобрять регистрацию.
     *
     * @return void
     */
    public function test_participant_cannot_approve_user(): void
    {
        /** @var User&Authenticatable $participant */
        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $this->actingAs($participant)
            ->postJson('/api/admin/users/'.User::factory()->pendingApproval()->create()->id.'/approve')
            ->assertForbidden();
    }

    /**
     * Проверяет одобрение пользователя администратором торгов.
     *
     * @return void
     */
    public function test_trade_admin_can_approve_user(): void
    {
        /** @var User&Authenticatable $administrator */
        $administrator = User::factory()->create();
        $administrator->assignRole('trade_admin');
        /** @var User&Authenticatable $user */
        $user = User::factory()->pendingApproval()->create();

        $this->actingAs($administrator)
            ->postJson("/api/admin/users/{$user->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', UserStatus::Active->value);
    }

    /**
     * Проверяет получение текущего пользователя и завершение его сессии.
     *
     * @return void
     */
    public function test_authenticated_user_can_read_me_and_log_out(): void
    {
        /** @var User&Authenticatable $user */
        $user = User::factory()->create();
        $user->assignRole('participant');

        $this->actingAs($user)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.roles.0', 'participant');

        $this->actingAs($user)
            ->postJson('/api/auth/logout')
            ->assertOk();
    }

    /**
     * Проверяет выпуск токена восстановления пароля и отправку письма.
     *
     * @return void
     */
    public function test_forgot_password_sends_mail(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        $this->postJson('/api/auth/password/forgot', ['email' => $user->email])
            ->assertOk();

        $this->assertDatabaseHas('password_reset_tokens', ['user_id' => $user->id]);
        Mail::assertSent(PasswordResetMail::class);
    }

    /**
     * Проверяет смену пароля по действующему одноразовому токену.
     *
     * @return void
     */
    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = User::factory()->create();
        $token = PasswordResetToken::query()->create([
            'user_id' => $user->id,
            'token' => 'valid-reset-token',
            'expires_at' => now()->addHour(),
        ]);

        $this->postJson('/api/auth/password/reset', [
            'token' => $token->token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk();

        $this->assertNotNull($token->refresh()->used_at);
    }

    /**
     * Проверяет создание обращения к администратору о восстановлении доступа.
     *
     * @return void
     */
    public function test_user_can_create_admin_password_reset_request(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/auth/password/admin-request', [
            'inn' => $user->inn,
            'message' => 'Нет доступа к почте.',
        ])->assertCreated();

        $this->assertDatabaseHas('password_reset_admin_requests', ['user_id' => $user->id]);
    }

    /**
     * Проверяет чтение и изменение только собственного профиля.
     *
     * @return void
     */
    public function test_user_can_manage_own_profile(): void
    {
        /** @var User&Authenticatable $user */
        $user = User::factory()->create();
        UserProfile::query()->create([
            'user_id' => $user->id,
            'entity_type' => 'legal',
            'name' => 'Старое наименование',
            'phone' => '+79990000000',
            'director_name' => 'Иванов Иван',
            'contact_persons' => 'Петров Пётр',
            'pd_consent_at' => now(),
        ]);

        $this->actingAs($user)->getJson('/api/profile')->assertOk();
        $this->actingAs($user)
            ->putJson('/api/profile', ['name' => 'Новое наименование'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Новое наименование');
    }

    /**
     * Проверяет загрузку собственного документа на локальный диск.
     *
     * @return void
     */
    public function test_user_can_upload_document(): void
    {
        Storage::fake('local');
        /** @var User&Authenticatable $user */
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/profile/documents', [
                'document' => UploadedFile::fake()->create('certificate.pdf', 100, 'application/pdf'),
            ])
            ->assertCreated();

        $document = $user->documents()->firstOrFail();
        Storage::disk('local')->assertExists($document->file_path);
    }
}
