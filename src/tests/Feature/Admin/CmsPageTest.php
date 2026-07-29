<?php

namespace Tests\Feature\Admin;

use App\Models\CmsPage;
use App\Models\CmsPageRevision;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature-тесты CRUD страниц CMS (фаза 4.1).
 */
class CmsPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Подготавливает роли RBAC.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * Гость не может читать список страниц CMS в админке.
     *
     * @return void
     */
    public function test_guest_cannot_list_cms_pages(): void
    {
        $this->getJson('/api/admin/cms-pages')->assertUnauthorized();
    }

    /**
     * trade_admin получает 403 на CRUD страниц CMS.
     *
     * @return void
     */
    public function test_trade_admin_cannot_manage_cms_pages(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $this->actingAs($admin)
            ->getJson('/api/admin/cms-pages')
            ->assertForbidden();
    }

    /**
     * super_admin создаёт страницу с первой ревизией.
     *
     * @return void
     */
    public function test_super_admin_can_create_cms_page_with_revision(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)
            ->postJson('/api/admin/cms-pages', [
                'slug' => 'about',
                'title' => 'О площадке',
                'meta_title' => 'О ЭТП',
                'is_published' => true,
                'sort_order' => 1,
                'content_html' => '<p>Добро пожаловать</p>',
            ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'about')
            ->assertJsonPath('data.content_html', '<p>Добро пожаловать</p>');

        $this->assertDatabaseHas('cms_pages', [
            'slug' => 'about',
            'is_published' => true,
        ]);

        $this->assertDatabaseHas('cms_page_revisions', [
            'content_html' => '<p>Добро пожаловать</p>',
            'revised_by' => $admin->id,
        ]);
    }

    /**
     * Обновление content_html создаёт новую ревизию (история версий).
     *
     * @return void
     */
    public function test_update_content_creates_new_revision(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $page = CmsPage::factory()->create(['slug' => 'rules']);
        CmsPageRevision::query()->create([
            'page_id' => $page->id,
            'content_html' => '<p>v1</p>',
            'revised_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->putJson('/api/admin/cms-pages/'.$page->id, [
                'title' => 'Правила обновлённые',
                'content_html' => '<p>v2</p>',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Правила обновлённые')
            ->assertJsonPath('data.content_html', '<p>v2</p>');

        $this->assertSame(2, CmsPageRevision::query()->where('page_id', $page->id)->count());
    }

    /**
     * show возвращает историю ревизий.
     *
     * @return void
     */
    public function test_show_includes_revision_history(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $page = CmsPage::factory()->create();
        CmsPageRevision::query()->create([
            'page_id' => $page->id,
            'content_html' => '<p>old</p>',
            'revised_by' => $admin->id,
        ]);
        CmsPageRevision::query()->create([
            'page_id' => $page->id,
            'content_html' => '<p>new</p>',
            'revised_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/cms-pages/'.$page->id)
            ->assertOk()
            ->assertJsonPath('data.content_html', '<p>new</p>')
            ->assertJsonCount(2, 'data.revisions');
    }

    /**
     * Мягкое удаление страницы CMS.
     *
     * @return void
     */
    public function test_super_admin_can_soft_delete_cms_page(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $page = CmsPage::factory()->create();

        $this->actingAs($admin)
            ->deleteJson('/api/admin/cms-pages/'.$page->id)
            ->assertOk();

        $this->assertSoftDeleted('cms_pages', ['id' => $page->id]);
    }
}
