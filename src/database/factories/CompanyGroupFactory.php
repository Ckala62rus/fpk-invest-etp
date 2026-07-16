<?php

namespace Database\Factories;

use App\Models\CompanyGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика групп компаний холдинга (1-й уровень классификатора).
 *
 * @extends Factory<CompanyGroup>
 */
class CompanyGroupFactory extends Factory
{
    /**
     * Активная группа компаний.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company().' Group',
            'sort_order' => fake()->numberBetween(0, 100),
            'is_active' => true,
        ];
    }
}
