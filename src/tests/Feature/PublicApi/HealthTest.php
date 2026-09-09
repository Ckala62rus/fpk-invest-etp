<?php

namespace Tests\Feature\PublicApi;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты проверки живости API (фаза 11.4).
 */
class HealthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Гость получает статус БД без авторизации.
     *
     * @return void
     */
    public function test_guest_can_get_health(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.database', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['database', 'redis', 'queue', 'broadcast'],
            ]);
    }
}
