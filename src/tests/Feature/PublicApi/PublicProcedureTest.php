<?php

namespace Tests\Feature\PublicApi;

use App\Enums\ProcedureStatus;
use App\Enums\ProcedureVisibility;
use App\Models\Procedure;
use App\Models\ProcedureDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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

    /**
     * Авторизованный пользователь скачивает документ открытой опубликованной процедуры.
     *
     * @return void
     */
    public function test_authenticated_user_can_download_document_of_open_published_procedure(): void
    {
        Storage::fake('local');

        $procedure = Procedure::factory()->published()->create([
            'visibility' => ProcedureVisibility::Open,
        ]);
        $path = 'procedure_documents/'.$procedure->id.'/tender.pdf';
        Storage::disk('local')->put($path, 'tender documentation');

        $uploader = User::factory()->create();
        $document = ProcedureDocument::query()->create([
            'procedure_id' => $procedure->id,
            'file_path' => $path,
            'file_name' => 'tender.pdf',
            'version' => 1,
            'uploaded_by' => $uploader->id,
        ]);

        $this->actingAs($uploader)
            ->get('/api/procedures/'.$procedure->id.'/documents/'.$document->id.'/download')
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=tender.pdf');
    }
}
