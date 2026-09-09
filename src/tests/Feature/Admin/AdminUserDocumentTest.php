<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\UserDocument;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Feature-тесты: админ смотрит и скачивает документы профиля участника.
 */
class AdminUserDocumentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('local');
    }

    /**
     * Участник загружает документ; админ видит список и скачивает файл.
     *
     * @return void
     */
    public function test_admin_can_list_and_download_participant_profile_document(): void
    {
        /** @var User&Authenticatable $participant */
        $participant = User::factory()->create();
        $participant->assignRole('participant');

        $this->actingAs($participant)
            ->postJson('/api/profile/documents', [
                'document' => UploadedFile::fake()->create('ustav.pdf', 100, 'application/pdf'),
            ])
            ->assertCreated();

        $document = UserDocument::query()->where('user_id', $participant->id)->firstOrFail();

        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)
            ->getJson("/api/admin/users/{$participant->id}/documents")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', $document->id)
            ->assertJsonPath('data.0.file_name', 'ustav.pdf');

        $this->actingAs($admin)
            ->get("/api/admin/users/{$participant->id}/documents/{$document->id}/download")
            ->assertOk();
    }

    /**
     * Участник не может читать чужие документы через admin API.
     *
     * @return void
     */
    public function test_participant_cannot_list_other_user_documents_via_admin(): void
    {
        /** @var User&Authenticatable $owner */
        $owner = User::factory()->create();
        $owner->assignRole('participant');

        UserDocument::query()->create([
            'user_id' => $owner->id,
            'file_path' => 'user_documents/1/x.pdf',
            'file_name' => 'x.pdf',
            'mime_type' => 'application/pdf',
            'size' => 10,
            'valid_until' => now()->addYear(),
            'uploaded_at' => now(),
        ]);

        /** @var User&Authenticatable $other */
        $other = User::factory()->create();
        $other->assignRole('participant');

        $this->actingAs($other)
            ->getJson("/api/admin/users/{$owner->id}/documents")
            ->assertForbidden();
    }

    /**
     * Владелец скачивает свой документ профиля.
     *
     * @return void
     */
    public function test_owner_can_download_own_profile_document(): void
    {
        /** @var User&Authenticatable $user */
        $user = User::factory()->create();
        $user->assignRole('participant');

        $this->actingAs($user)
            ->postJson('/api/profile/documents', [
                'document' => UploadedFile::fake()->create('rekvizity.pdf', 50, 'application/pdf'),
            ])
            ->assertCreated();

        $document = $user->documents()->firstOrFail();

        $this->actingAs($user)
            ->get("/api/profile/documents/{$document->id}/download")
            ->assertOk();
    }
}
