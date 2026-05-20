<?php

namespace Database\Factories;

use App\Models\AccountingPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccountingPeriod>
 */
class AccountingPeriodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'year' => fake()->unique()->numberBetween(2020, 2030),
            'is_closed' => false,
            'monthly_tax_paid' => 0.00,
            'tax_calculator' => null,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_closed' => true,
        ]);
    }
}
