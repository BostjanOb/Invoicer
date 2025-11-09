<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Models\AccountingPeriod;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Create accounting periods (2023-2025)
        $accountingPeriods = [
            AccountingPeriod::firstOrCreate(['year' => 2023], ['is_closed' => true]),
            AccountingPeriod::firstOrCreate(['year' => 2024], ['is_closed' => false]),
            AccountingPeriod::firstOrCreate(['year' => 2025], ['is_closed' => false]),
        ];

        // Create customers
        $customers = Customer::factory()->count(12)->create();

        // Invoice number counter per period
        $invoiceNumbers = [
            2023 => 1,
            2024 => 1,
            2025 => 1,
        ];

        // Create ~20 invoices per accounting period
        foreach ($accountingPeriods as $period) {
            // Determine status distribution based on period
            $statusDistribution = match ($period->year) {
                2023 => [
                    'paid' => 18,    // Most paid in closed period
                    'issued' => 2,   // A few still outstanding
                    'draft' => 0,    // No drafts in closed period
                ],
                2024 => [
                    'paid' => 12,    // Half paid
                    'issued' => 6,   // Some issued but not paid
                    'draft' => 2,    // A couple drafts
                ],
                default => [
                    'paid' => 5,     // Some paid
                    'issued' => 8,   // More issued
                    'draft' => 7,    // Many drafts for future
                ],
            };

            foreach ($statusDistribution as $statusValue => $count) {
                for ($i = 0; $i < $count; $i++) {
                    $customer = $customers->random();

                    $invoice = match ($statusValue) {
                        'draft' => Invoice::factory()
                            ->for($period, 'accountingPeriod')
                            ->for($customer)
                            ->create([
                                'status' => InvoiceStatus::DRAFT,
                                'number' => null,
                                'issue_date' => null,
                                'paid_at' => null,
                                'payment_deadline' => now()->addDays(fake()->numberBetween(30, 60)),
                            ]),

                        'issued' => Invoice::factory()
                            ->for($period, 'accountingPeriod')
                            ->for($customer)
                            ->issued($invoiceNumbers[$period->year]++)
                            ->create([
                                'payment_deadline' => now()->subDays(fake()->numberBetween(0, 30)),
                            ]),

                        'paid' => Invoice::factory()
                            ->for($period, 'accountingPeriod')
                            ->for($customer)
                            ->paid($invoiceNumbers[$period->year]++)
                            ->create(),
                    };

                    // Create 1-5 invoice items per invoice
                    InvoiceItem::factory()
                        ->count(fake()->numberBetween(1, 5))
                        ->for($invoice)
                        ->create();
                }
            }
        }
    }
}
