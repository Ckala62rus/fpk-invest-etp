<?php

namespace Database\Seeders;

use App\Models\ClassifierCategory;
use App\Models\Company;
use App\Models\CompanyGroup;
use Illuminate\Database\Seeder;

/**
 * Демо-каталог холдинга: группы компаний, категории классификатора, предприятия-заказчики.
 *
 * Нужен для локальной разработки SPA (админка классификатора, создание ТЗП, витрина).
 * Идемпотентный: ключи — фиксированные имена / ИНН.
 */
class DemoCatalogSeeder extends Seeder
{
    /**
     * Создаёт или обновляет демо-группы, категории и компании.
     *
     * @return void
     */
    public function run(): void
    {
        $fpk = CompanyGroup::query()->updateOrCreate(
            ['name' => 'ФПК «Инвест» (демо)'],
            [
                'sort_order' => 10,
                'is_active' => true,
            ],
        );

        $external = CompanyGroup::query()->updateOrCreate(
            ['name' => 'Внешние заказчики (демо)'],
            [
                'sort_order' => 20,
                'is_active' => true,
            ],
        );

        $categories = [
            ['name' => 'СМР (строительно-монтажные работы)', 'sort_order' => 10],
            ['name' => 'ПИР (проектно-изыскательские работы)', 'sort_order' => 20],
            ['name' => 'ИТ (информационные технологии)', 'sort_order' => 30],
            ['name' => 'Оборудование', 'sort_order' => 40],
            ['name' => 'Услуги', 'sort_order' => 50],
        ];

        foreach ($categories as $row) {
            ClassifierCategory::query()->updateOrCreate(
                [
                    'company_group_id' => $fpk->id,
                    'name' => $row['name'],
                ],
                [
                    'sort_order' => $row['sort_order'],
                    'is_active' => true,
                ],
            );
        }

        $companies = [
            [
                'inn' => '7701000001',
                'name' => 'ООО «ФПК Инвест — Центр»',
                'company_group_id' => $fpk->id,
                'is_external' => false,
            ],
            [
                'inn' => '7701000002',
                'name' => 'АО «ФПК Инвест — Регион»',
                'company_group_id' => $fpk->id,
                'is_external' => false,
            ],
            [
                'inn' => '7701000003',
                'name' => 'ООО «СеверСтрой» (внешний)',
                'company_group_id' => $external->id,
                'is_external' => true,
            ],
        ];

        foreach ($companies as $row) {
            Company::query()->updateOrCreate(
                ['inn' => $row['inn']],
                [
                    'name' => $row['name'],
                    'company_group_id' => $row['company_group_id'],
                    'is_external' => $row['is_external'],
                    'is_active' => true,
                ],
            );
        }
    }
}
