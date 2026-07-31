<?php

namespace Tests\Feature;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use App\Models\ProposalDocument;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Feature-тесты документов КП (фаза 6.2).
 */
class ProposalDocumentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Подготавливает роли RBAC и фейковый диск.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('local');
    }

    /**
     * Владелец заявки загружает документ.
     *
     * @return void
     */
    public function test_owner_can_upload_document(): void
    {
        /** @var User&Authenticatable $participant */
        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $proposal = Proposal::factory()->submitted()->create([
            'user_id' => $participant->id,
        ]);

        $file = UploadedFile::fake()->create('kp.pdf', 100, 'application/pdf');

        $this->actingAs($participant)
            ->postJson('/api/proposals/'.$proposal->id.'/documents', [
                'document' => $file,
                'type' => 'commercial_offer',
            ])
            ->assertCreated()
            ->assertJsonPath('data.file_name', 'kp.pdf')
            ->assertJsonPath('data.type', 'commercial_offer')
            ->assertJsonPath('message', 'Документ загружен.');

        $this->assertDatabaseHas('proposal_documents', [
            'proposal_id' => $proposal->id,
            'file_name' => 'kp.pdf',
            'type' => 'commercial_offer',
        ]);
    }

    /**
     * Список документов своей заявки.
     *
     * @return void
     */
    public function test_owner_can_list_documents(): void
    {
        /** @var User&Authenticatable $participant */
        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $proposal = Proposal::factory()->submitted()->create([
            'user_id' => $participant->id,
        ]);

        ProposalDocument::query()->create([
            'proposal_id' => $proposal->id,
            'file_path' => 'proposal_documents/1/a.pdf',
            'file_name' => 'a.pdf',
            'type' => null,
        ]);

        $this->actingAs($participant)
            ->getJson('/api/proposals/'.$proposal->id.'/documents')
            ->assertOk()
            ->assertJsonPath('data.0.file_name', 'a.pdf');
    }

    /**
     * Чужую заявку нельзя трогать.
     *
     * @return void
     */
    public function test_cannot_upload_to_foreign_proposal(): void
    {
        /** @var User&Authenticatable $owner */
        $owner = User::factory()->create();
        $owner->assignRole('participant');

        /** @var User&Authenticatable $other */
        $other = User::factory()->create();
        $other->assignRole('participant');

        $proposal = Proposal::factory()->submitted()->create([
            'user_id' => $owner->id,
        ]);

        $this->actingAs($other)
            ->postJson('/api/proposals/'.$proposal->id.'/documents', [
                'document' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
            ])
            ->assertForbidden();
    }

    /**
     * Скачивание своего документа.
     *
     * @return void
     */
    public function test_owner_can_download_document(): void
    {
        /** @var User&Authenticatable $participant */
        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $proposal = Proposal::factory()->submitted()->create([
            'user_id' => $participant->id,
        ]);

        $path = UploadedFile::fake()
            ->create('offer.pdf', 20, 'application/pdf')
            ->store("proposal_documents/{$proposal->id}", 'local');

        $document = ProposalDocument::query()->create([
            'proposal_id' => $proposal->id,
            'file_path' => $path,
            'file_name' => 'offer.pdf',
            'type' => 'commercial_offer',
        ]);

        $this->actingAs($participant)
            ->get('/api/proposals/'.$proposal->id.'/documents/'.$document->id.'/download')
            ->assertOk();
    }

    /**
     * Удаление документа владельцем.
     *
     * @return void
     */
    public function test_owner_can_delete_document(): void
    {
        /** @var User&Authenticatable $participant */
        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $proposal = Proposal::factory()->submitted()->create([
            'user_id' => $participant->id,
        ]);

        $path = UploadedFile::fake()
            ->create('del.pdf', 10, 'application/pdf')
            ->store("proposal_documents/{$proposal->id}", 'local');

        $document = ProposalDocument::query()->create([
            'proposal_id' => $proposal->id,
            'file_path' => $path,
            'file_name' => 'del.pdf',
            'type' => null,
        ]);

        $this->actingAs($participant)
            ->deleteJson('/api/proposals/'.$proposal->id.'/documents/'.$document->id)
            ->assertOk()
            ->assertJsonPath('message', 'Документ удалён.');

        $this->assertDatabaseMissing('proposal_documents', ['id' => $document->id]);
        Storage::disk('local')->assertMissing($path);
    }

    /**
     * После допуска документы менять нельзя.
     *
     * @return void
     */
    public function test_cannot_upload_when_admitted(): void
    {
        /** @var User&Authenticatable $participant */
        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $proposal = Proposal::factory()->admitted()->create([
            'user_id' => $participant->id,
            'status' => ProposalStatus::Admitted,
        ]);

        $this->actingAs($participant)
            ->postJson('/api/proposals/'.$proposal->id.'/documents', [
                'document' => UploadedFile::fake()->create('late.pdf', 10, 'application/pdf'),
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Документы нельзя изменить на текущем статусе заявки.');
    }

    /**
     * Гость — 401.
     *
     * @return void
     */
    public function test_guest_cannot_upload(): void
    {
        $proposal = Proposal::factory()->submitted()->create();

        $this->postJson('/api/proposals/'.$proposal->id.'/documents', [
            'document' => UploadedFile::fake()->create('g.pdf', 10, 'application/pdf'),
        ])->assertUnauthorized();
    }
}
