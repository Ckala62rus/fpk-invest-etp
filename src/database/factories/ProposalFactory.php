<?php

namespace Database\Factories;

use App\Enums\ProposalStatus;
use App\Models\Procedure;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика заявок участников на запрос предложений (КП).
 *
 * @extends Factory<Proposal>
 */
class ProposalFactory extends Factory
{
    /**
     * Черновик заявки (ещё не подана).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'procedure_id' => Procedure::factory(),
            'user_id' => User::factory(),
            'status' => ProposalStatus::Draft,
            'submitted_at' => null,
            'contract_form_agreed_at' => null,
            'version' => 1,
            'parent_proposal_id' => null,
        ];
    }

    /**
     * Заявка подана участником.
     *
     * @return static
     */
    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProposalStatus::Submitted,
            'submitted_at' => now(),
            'contract_form_agreed_at' => now(),
        ]);
    }

    /**
     * Заявка на рассмотрении.
     *
     * @return static
     */
    public function underReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProposalStatus::UnderReview,
            'submitted_at' => now()->subDay(),
            'contract_form_agreed_at' => now()->subDay(),
        ]);
    }

    /**
     * Заявка допущена к участию.
     *
     * @return static
     */
    public function admitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProposalStatus::Admitted,
            'submitted_at' => now()->subDays(3),
            'contract_form_agreed_at' => now()->subDays(3),
        ]);
    }

    /**
     * Заявка отклонена.
     *
     * @return static
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProposalStatus::Rejected,
            'submitted_at' => now()->subDays(3),
            'contract_form_agreed_at' => now()->subDays(3),
        ]);
    }

    /**
     * Заявка-победитель.
     *
     * @return static
     */
    public function winner(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProposalStatus::Winner,
            'submitted_at' => now()->subDays(7),
            'contract_form_agreed_at' => now()->subDays(7),
        ]);
    }
}
