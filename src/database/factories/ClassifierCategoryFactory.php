<?php

namespace Database\Factories;

use App\Models\ClassifierCategory;
use App\Models\CompanyGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика категорий предмета закупки (2-й уровень классификатора).
 *
 * @extends Factory<ClassifierCategory>
 */
class ClassifierCategoryFactory extends Factory
{
    /**
     * Активная категория закупки.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_group_id' => CompanyGroup::factory(),
            'name' => fake()->randomElement([
                'СМР (строительно-монтажные работы)',
                'ПИР (проектно-изыскательские работы)',
                'ИТ (информационные технологии)',
                'Оборудование',
                'Услуги',
            ]),
            'sort_order' => fake()->numberBetween(0, 100),
            'is_active' => true,
        ];
    }
}
