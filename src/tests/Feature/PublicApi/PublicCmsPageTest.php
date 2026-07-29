<?php

namespace Tests\Feature\PublicApi;

use App\Models\CmsPage;
use App\Models\CmsPageRevision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты публичного API страниц CMS (фаза 4.2).
 */
class PublicCmsPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Гость видит только опубликованные страницы в списке.
     *
     * @return void
     */
    public function test_guest_sees_only_published_pages(): void
    {
        $published = CmsPage::factory()->published()->create(['slug' => 'about', 'title' => 'О площадке']);
        CmsPage::factory()->create(['slug' => 'draft', 'is_published' => false]);

        $this->getJson('/api/cms/pages')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'about')
            ->assertJsonPath('data.0.id', $published->id);
    }

    /**
     * Гость читает опубликованную страницу по slug с HTML.
     *
     * @return void
     */
    public function test_guest_can_view_published_page_by_slug(): void
    {
        $admin = User::factory()->create();
        $page = CmsPage::factory()->published()->create(['slug' => 'privacy']);
        CmsPageRevision::query()->create([
            'page_id' => $page->id,
            'content_html' => '<p>ПДн</p>',
            'revised_by' => $admin->id,
        ]);

        $this->getJson('/api/cms/pages/privacy')
            ->assertOk()
            ->assertJsonPath('data.slug', 'privacy')
            ->assertJsonPath('data.content_html', '<p>ПДн</p>');
    }

    /**
     * Неопубликованная страница недоступна гостю (404).
     *
     * @return void
     */
    public function test_guest_cannot_view_unpublished_page(): void
    {
        CmsPage::factory()->create(['slug' => 'secret', 'is_published' => false]);

        $this->getJson('/api/cms/pages/secret')
            ->assertNotFound();
    }
}
