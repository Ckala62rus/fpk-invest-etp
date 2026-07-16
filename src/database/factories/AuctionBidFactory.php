<?php

namespace Database\Factories;

use App\Models\AuctionBid;
use App\Models\ProcedureLot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика ставок участников на аукционе.
 *
 * @extends Factory<AuctionBid>
 */
class AuctionBidFactory extends Factory
{
    /**
     * Активная ставка (не отменена) по лоту аукциона.
     *
     * procedure_id берётся из того же лота, чтобы FK не разъезжались.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lot_id' => ProcedureLot::factory(),
            'procedure_id' => function (array $attributes) {
                return ProcedureLot::query()->findOrFail($attributes['lot_id'])->procedure_id;
            },
            'user_id' => User::factory(),
            'amount' => fake()->randomFloat(2, 1_000, 500_000),
            'is_cancelled' => false,
            'cancelled_by' => null,
            'cancelled_at' => null,
            'cancel_reason' => null,
            'ip_address' => fake()->ipv4(),
        ];
    }

    /**
     * Ставка отменена администратором (ставки не удаляются).
     *
     * @return static
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_cancelled' => true,
            'cancelled_by' => User::factory(),
            'cancelled_at' => now(),
            'cancel_reason' => 'Отмена администратором торгов',
        ]);
    }
}
