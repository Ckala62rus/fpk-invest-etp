<?php

namespace Database\Factories;

use App\Enums\ProcedureStatus;
use App\Enums\ProcedureType;
use App\Enums\ProcedureVisibility;
use App\Enums\TradeDirection;
use App\Models\ClassifierCategory;
use App\Models\Company;
use App\Models\Procedure;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика торгово-закупочных процедур (ТЗП).
 *
 * @extends Factory<Procedure>
 */
class ProcedureFactory extends Factory
{
    /**
     * Черновик ТЗП (торгово-закупочной процедуры) со всеми обязательными FK.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now();

        return [
            'number' => 'TZP-'.fake()->unique()->numerify('######'),
            'title' => fake()->sentence(6),
            'description' => fake()->optional()->paragraph(),
            'type' => ProcedureType::RequestForProposal,
            'trade_direction' => TradeDirection::Purchase,
            'status' => ProcedureStatus::Draft,
            'visibility' => ProcedureVisibility::Open,
            'company_id' => Company::factory(),
            'classifier_category_id' => ClassifierCategory::factory(),
            'responsible_user_id' => User::factory(),
            'created_by' => User::factory(),
            'customer_contact_name' => fake()->optional()->name(),
            'customer_contact_email' => fake()->optional()->companyEmail(),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addDays(7),
            'published_at' => null,
            'completed_at' => null,
            'results_published' => false,
            'storage_years' => 3,
            'source_procedure_id' => null,
            'deleted_by' => null,
        ];
    }

    /**
     * Опубликованная процедура в приёме заявок.
     *
     * @return static
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProcedureStatus::Published,
            'published_at' => now(),
            'completed_at' => null,
            'results_published' => false,
        ]);
    }

    /**
     * Процедура в статусе приёма заявок.
     *
     * @return static
     */
    public function accepting(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProcedureStatus::Accepting,
            'published_at' => now()->subDay(),
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(7),
            'completed_at' => null,
        ]);
    }

    /**
     * Электронный аукцион.
     *
     * @return static
     */
    public function auction(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ProcedureType::Auction,
            'status' => ProcedureStatus::AuctionPending,
            'published_at' => now(),
            'completed_at' => null,
        ]);
    }

    /**
     * Завершённая процедура.
     *
     * @return static
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProcedureStatus::Completed,
            'published_at' => now()->subDays(14),
            'starts_at' => now()->subDays(14),
            'ends_at' => now()->subDay(),
            'completed_at' => now(),
            'results_published' => true,
        ]);
    }
}
