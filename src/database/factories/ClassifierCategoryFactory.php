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
            'name' => fake()->randomElement(['СМР', 'ПИР', 'ИТ', 'Оборудование', 'Услуги']),
            'sort_order' => fake()->numberBetween(0, 100),
            'is_active' => true,
        ];
    }
}
