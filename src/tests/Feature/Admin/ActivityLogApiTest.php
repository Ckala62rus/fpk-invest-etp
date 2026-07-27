<?php

namespace Tests\Feature\Admin;

use App\Enums\UserStatus;
use App\Models\ActivityLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты API журнала аудита (фаза 2.6).
 */
class ActivityLogApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Подготавливает роли RBAC (role-based access control).
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * Гость не читает журнал аудита.
     *
     * @return void
     */
    public function test_guest_cannot_list_activity_logs(): void
    {
        $this->getJson('/api/admin/activity-logs')->assertUnauthorized();
    }

    /**
     * Участник получает 403.
     *
     * @return void
     */
    public function test_participant_cannot_list_activity_logs(): void
    {
        /** @var User&Authenticatable $participant */
        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $this->actingAs($participant)
            ->getJson('/api/admin/activity-logs')
            ->assertForbidden();
    }

    /**
     * Аудитор читает список аудита (право activity_log.view).
     *
     * @return void
     */
    public function test_auditor_can_list_activity_logs(): void
    {
        /** @var User&Authenticatable $auditor */
        $auditor = User::factory()->create();
        $auditor->assignRole('auditor');

        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $target = User::factory()->create();
        $target->assignRole('participant');

        $this->actingAs($admin)
            ->postJson("/api/admin/users/{$target->id}/block", [
                'reason' => 'Нарушение регламента для аудита',
            ])
            ->assertOk();

        $this->actingAs($auditor)
            ->getJson('/api/admin/activity-logs')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Журнал аудита.')
            ->assertJsonStructure([
                'data' => [
                    ['id', 'log_name', 'description', 'event', 'causer_id', 'properties', 'created_at'],
                ],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);
    }

    /**
     * Фильтр event=blocked возвращает только блокировки.
     *
     * @return void
     */
    public function test_can_filter_activity_logs_by_event(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $target = User::factory()->create();
        $target->assignRole('participant');

        $this->actingAs($admin)
            ->postJson("/api/admin/users/{$target->id}/block", [
                'reason' => 'Фильтр по событию blocked',
            ])
            ->assertOk();

        $this->actingAs($admin)
            ->putJson("/api/admin/users/{$target->id}/roles", [
                'roles' => ['auditor'],
            ])
            ->assertOk();

        $this->actingAs($admin)
            ->getJson('/api/admin/activity-logs?event=blocked')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.event', 'blocked');
    }

    /**
     * Фильтр causer_id ограничивает записи инициатором.
     *
     * @return void
     */
    public function test_can_filter_activity_logs_by_causer(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $otherAdmin = User::factory()->create();
        $otherAdmin->assignRole('trade_admin');

        $target = User::factory()->create();
        $target->assignRole('participant');

        $this->actingAs($admin)
            ->postJson("/api/admin/users/{$target->id}/block", [
                'reason' => 'Блокировка от первого админа',
            ])
            ->assertOk();

        $this->actingAs($admin)
            ->getJson('/api/admin/activity-logs?causer_id='.$admin->id)
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.causer_id', $admin->id);

        $this->actingAs($admin)
            ->getJson('/api/admin/activity-logs?causer_id='.$otherAdmin->id)
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    /**
     * Фильтр subject_type=user принимает короткий alias.
     *
     * @return void
     */
    public function test_can_filter_by_subject_type_alias(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $target = User::factory()->create();
        $target->assignRole('participant');

        $this->actingAs($admin)
            ->postJson("/api/admin/users/{$target->id}/block", [
                'reason' => 'Alias subject_type',
            ])
            ->assertOk();

        $this->actingAs($admin)
            ->getJson('/api/admin/activity-logs?subject_type=user&subject_id='.$target->id.'&event=blocked')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.subject_id', $target->id)
            ->assertJsonPath('data.0.event', 'blocked');
    }

    /**
     * Просмотр одной записи аудита.
     *
     * @return void
     */
    public function test_auditor_can_show_activity_log(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        /** @var User&Authenticatable $auditor */
        $auditor = User::factory()->create();
        $auditor->assignRole('auditor');

        $target = User::factory()->create();
        $target->assignRole('participant');

        $this->actingAs($admin)
            ->postJson("/api/admin/users/{$target->id}/block", [
                'reason' => 'Для карточки аудита',
            ])
            ->assertOk();

        $log = ActivityLog::query()->where('event', 'blocked')->firstOrFail();

        $this->actingAs($auditor)
            ->getJson("/api/admin/activity-logs/{$log->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $log->id)
            ->assertJsonPath('data.event', 'blocked')
            ->assertJsonPath('data.causer.id', $admin->id);
    }

    /**
     * Несуществующая запись — 404.
     *
     * @return void
     */
    public function test_show_missing_activity_log_returns_404(): void
    {
        /** @var User&Authenticatable $auditor */
        $auditor = User::factory()->create();
        $auditor->assignRole('auditor');

        $this->actingAs($auditor)
            ->getJson('/api/admin/activity-logs/999999')
            ->assertNotFound();
    }

    /**
     * Некорректный per_page — 422.
     *
     * @return void
     */
    public function test_invalid_per_page_returns_validation_error(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)
            ->getJson('/api/admin/activity-logs?per_page=0')
            ->assertUnprocessable();
    }

    /**
     * Заблокированный пользователь после block имеет статус blocked в БД (smoke цепочки).
     *
     * @return void
     */
    public function test_block_creates_activity_log_row(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $target = User::factory()->create();
        $target->assignRole('participant');

        $this->actingAs($admin)
            ->postJson("/api/admin/users/{$target->id}/block", [
                'reason' => 'Проверка записи в activity_log',
            ])
            ->assertOk();

        $this->assertDatabaseHas('activity_log', [
            'event' => 'blocked',
            'log_name' => 'user',
            'subject_id' => $target->id,
            'causer_id' => $admin->id,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'status' => UserStatus::Blocked->value,
        ]);
    }
}
