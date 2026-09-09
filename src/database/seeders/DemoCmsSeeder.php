<?php

namespace Database\Seeders;

use App\Models\CmsPage;
use App\Models\CmsPageRevision;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Демо-страницы CMS (информационный раздел витрины) с актуальной ревизией HTML.
 *
 * Идемпотентный по slug. Автор ревизий — super_admin.
 */
class DemoCmsSeeder extends Seeder
{
    /**
     * Создаёт или обновляет опубликованные CMS-страницы.
     *
     * @return void
     */
    public function run(): void
    {
        $admin = User::query()->where('inn', env('SUPER_ADMIN_INN', '770000000000'))->first();

        if ($admin === null) {
            $this->command?->warn('DemoCmsSeeder: нет super_admin — сначала SuperAdminSeeder.');

            return;
        }

        $pages = [
            [
                'slug' => 'about',
                'title' => 'О площадке',
                'meta_title' => 'О ЭТП ФПК «Инвест»',
                'meta_description' => 'Информация об электронной торговой площадке',
                'sort_order' => 10,
                'html' => '<h2>Электронная торговая площадка</h2><p>Демо-страница «О площадке» для локальной разработки SPA.</p><p>Заказчики публикуют ТЗП (торгово-закупочные процедуры), участники подают КП (коммерческие предложения) и участвуют в аукционах.</p>',
            ],
            [
                'slug' => 'rules',
                'title' => 'Правила участия',
                'meta_title' => 'Правила участия на ЭТП',
                'meta_description' => 'Правила регистрации и участия в процедурах',
                'sort_order' => 20,
                'html' => '<h2>Правила участия</h2><p>Регистрация по ИНН, подтверждение email, одобрение администратором.</p><ul><li>Не разглашать чужие ставки и КП.</li><li>Соблюдать сроки подачи предложений.</li><li>Документы профиля обновлять не реже раза в год.</li></ul>',
            ],
            [
                'slug' => 'contacts',
                'title' => 'Контакты',
                'meta_title' => 'Контакты ЭТП',
                'meta_description' => 'Служба поддержки площадки',
                'sort_order' => 30,
                'html' => '<h2>Контакты</h2><p>Email поддержки: <a href="mailto:support@fpk-invest.demo">support@fpk-invest.demo</a></p><p>Телефон: +7 (495) 000-00-00 (демо)</p>',
            ],
        ];

        foreach ($pages as $row) {
            $page = CmsPage::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'title' => $row['title'],
                    'meta_title' => $row['meta_title'],
                    'meta_description' => $row['meta_description'],
                    'is_published' => true,
                    'sort_order' => $row['sort_order'],
                ],
            );

            $latest = $page->revisions()->latest('id')->first();

            if ($latest === null || $latest->content_html !== $row['html']) {
                CmsPageRevision::query()->create([
                    'page_id' => $page->id,
                    'content_html' => $row['html'],
                    'revised_by' => $admin->id,
                ]);
            }
        }
    }
}
