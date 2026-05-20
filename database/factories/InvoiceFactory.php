<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\AccountingPeriod;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'accounting_period_id' => AccountingPeriod::factory(),
            'number' => null,
            'status' => InvoiceStatus::DRAFT,
            'issue_date' => null,
            'payment_deadline' => fake()->dateTimeBetween('+1 week', '+1 month'),
            'paid_at' => null,
            'service_text' => fake()->optional(0.3)->sentence(),
            'payout_amount' => 0,
        ];
    }

    public function withPayout(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'payout_amount' => $amount,
        ]);
    }

    public function issued(?int $number = null): static
    {
        return $this->state(fn (array $attributes) => [
            'number' => $number,
            'status' => InvoiceStatus::ISSUED,
            'issue_date' => now(),
        ]);
    }

    public function paid(?int $number = null): static
    {
        return $this->state(fn (array $attributes) => [
            'number' => $number,
            'status' => InvoiceStatus::PAID,
            'issue_date' => now()->subDays(10),
            'paid_at' => now(),
        ]);
    }
}
