<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика предприятий-заказчиков.
 *
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Активное предприятие внутри группы компаний.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_group_id' => CompanyGroup::factory(),
            'name' => fake()->company(),
            'inn' => fake()->unique()->numerify('##########'),
            'is_external' => false,
            'is_active' => true,
        ];
    }
}
