<?php

namespace Tests\Feature\PublicApi;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты endpoint времени сервера (фаза 4.4).
 */
class ServerTimeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Гость получает время сервера без авторизации.
     *
     * @return void
     */
    public function test_guest_can_get_server_time(): void
    {
        $this->getJson('/api/server-time')
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'datetime',
                    'timestamp',
                    'timezone',
                ],
            ])
            ->assertJsonPath('success', true);
    }
}
