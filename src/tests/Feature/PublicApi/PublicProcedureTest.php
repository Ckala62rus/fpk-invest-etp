<?php

namespace Tests\Feature\PublicApi;

use App\Enums\ProcedureStatus;
use App\Enums\ProcedureVisibility;
use App\Models\Procedure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты публичного списка ТЗП (фаза 4.3).
 */
class PublicProcedureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Гость видит только открытые опубликованные процедуры.
     *
     * @return void
     */
    public function test_guest_sees_only_open_published_procedures(): void
    {
        $visible = Procedure::factory()->published()->create([
            'visibility' => ProcedureVisibility::Open,
            'title' => 'Открытая закупка',
        ]);

        Procedure::factory()->create([
            'status' => ProcedureStatus::Draft,
            'visibility' => ProcedureVisibility::Open,
        ]);

        Procedure::factory()->published()->create([
            'visibility' => ProcedureVisibility::Closed,
        ]);

        $this->getJson('/api/procedures')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $visible->id);

        $this->assertArrayNotHasKey(
            'customer_contact_name',
            $this->getJson('/api/procedures')->json('data.0'),
        );
    }

    /**
     * Фильтр по типу ТЗП.
     *
     * @return void
     */
    public function test_guest_can_filter_by_type(): void
    {
        Procedure::factory()->published()->create();
        $auction = Procedure::factory()->auction()->create([
            'visibility' => ProcedureVisibility::Open,
        ]);

        $this->getJson('/api/procedures?type=auction')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $auction->id);
    }

    /**
     * Публичная карточка открытой ТЗП; закрытая — 404.
     *
     * @return void
     */
    public function test_guest_can_view_open_procedure_card(): void
    {
        $open = Procedure::factory()->accepting()->create([
            'visibility' => ProcedureVisibility::Open,
        ]);
        $closed = Procedure::factory()->published()->create([
            'visibility' => ProcedureVisibility::Closed,
        ]);

        $this->getJson('/api/procedures/'.$open->id)
            ->assertOk()
            ->assertJsonPath('data.id', $open->id)
            ->assertJsonPath('data.number', $open->number);

        $this->getJson('/api/procedures/'.$closed->id)
            ->assertNotFound();
    }

    /**
     * Поиск по названию.
     *
     * @return void
     */
    public function test_guest_can_search_by_title(): void
    {
        Procedure::factory()->published()->create(['title' => 'Поставка бетона']);
        Procedure::factory()->published()->create(['title' => 'Аренда техники']);

        $this->getJson('/api/procedures?search=бетона')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Поставка бетона');
    }
}
