<?php

namespace Database\Factories;

use App\Models\Procedure;
use App\Models\ProcedureLot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика лотов аукциона.
 *
 * @extends Factory<ProcedureLot>
 */
class ProcedureLotFactory extends Factory
{
    /**
     * Лот аукциона с начальной ценой и шагом ставки.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startPrice = fake()->randomFloat(2, 10_000, 1_000_000);

        return [
            'procedure_id' => Procedure::factory()->auction(),
            'sort_order' => fake()->numberBetween(0, 20),
            'name' => fake()->sentence(4),
            'unit' => fake()->optional()->randomElement(['шт', 'м2', 'м3', 'кг', 'компл.']),
            'quantity' => fake()->optional()->randomFloat(3, 1, 1000),
            'start_price' => $startPrice,
            'bid_step' => round($startPrice * 0.01, 2),
            'current_price' => null,
            'winner_user_id' => null,
        ];
    }
}
