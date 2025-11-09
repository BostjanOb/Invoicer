<?php

namespace Database\Factories;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $services = [
            'Website Development',
            'Mobile App Development',
            'UI/UX Design',
            'Consulting Services',
            'Software Maintenance',
            'Cloud Hosting',
            'Database Management',
            'API Integration',
            'Quality Assurance Testing',
            'Project Management',
        ];

        return [
            'invoice_id' => Invoice::factory(),
            'title' => fake()->randomElement($services),
            'description' => fake()->optional(0.5)->sentence(),
            'price' => fake()->randomFloat(2, 50, 5000),
            'quantity' => fake()->numberBetween(1, 10),
            'sort' => 0,
        ];
    }
}
