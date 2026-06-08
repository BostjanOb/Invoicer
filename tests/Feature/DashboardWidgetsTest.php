<?php

use App\Enums\InvoiceStatus;
use App\Filament\Widgets\AccountingPeriodSelector;
use App\Filament\Widgets\LatestIssuedInvoices;
use App\Filament\Widgets\RevenueTrends;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\TopCustomers;
use App\Models\AccountingPeriod;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('displays the accounting period selector on the dashboard', function () {
    AccountingPeriod::factory()->create(['year' => 2024]);

    Livewire::test(AccountingPeriodSelector::class)
        ->assertOk();
});

it('can select an accounting period', function () {
    $period = AccountingPeriod::factory()->create(['year' => 2024]);

    Livewire::test(AccountingPeriodSelector::class)
        ->set('selectedPeriodId', $period->id)
        ->assertSet('selectedPeriodId', $period->id);
});

it('defaults to the latest non-closed accounting period', function () {
    AccountingPeriod::factory()->closed()->create(['year' => 2023]);
    $openPeriod = AccountingPeriod::factory()->create(['year' => 2024]);

    Livewire::test(AccountingPeriodSelector::class)
        ->assertSet('selectedPeriodId', $openPeriod->id);
});

it('displays stats overview with correct revenue data', function () {
    $period = AccountingPeriod::factory()->create(['year' => 2024]);
    $customer = Customer::factory()->create();

    $invoice = Invoice::factory()
        ->for($period)
        ->for($customer)
        ->paid()
        ->create();

    InvoiceItem::factory()
        ->for($invoice)
        ->create(['price' => 1000, 'quantity' => 2]);

    Livewire::test(StatsOverview::class, ['selectedPeriodId' => $period->id])
        ->assertSee('Total Revenue')
        ->assertSee('€2,000.00');
});

it('displays outstanding amount for issued invoices', function () {
    $period = AccountingPeriod::factory()->create(['year' => 2024]);
    $customer = Customer::factory()->create();

    $invoice = Invoice::factory()
        ->for($period)
        ->for($customer)
        ->issued()
        ->create();

    InvoiceItem::factory()
        ->for($invoice)
        ->create(['price' => 500, 'quantity' => 3]);

    Livewire::test(StatsOverview::class, ['selectedPeriodId' => $period->id])
        ->assertSee('Outstanding Amount')
        ->assertSee('€1,500.00');
});

it('displays draft invoice value', function () {
    $period = AccountingPeriod::factory()->create(['year' => 2024]);
    $customer = Customer::factory()->create();

    $invoice = Invoice::factory()
        ->for($period)
        ->for($customer)
        ->create(['status' => InvoiceStatus::DRAFT]);

    InvoiceItem::factory()
        ->for($invoice)
        ->create(['price' => 250, 'quantity' => 4]);

    Livewire::test(StatsOverview::class, ['selectedPeriodId' => $period->id])
        ->assertSee('Draft Value')
        ->assertSee('€1,000.00');
});

it('compares revenue against the previous year up to the same point in the year', function () {
    $currentPeriod = AccountingPeriod::factory()->create(['year' => 2024]);
    $previousPeriod = AccountingPeriod::factory()->create(['year' => 2023]);
    $customer = Customer::factory()->create();

    // Previous year revenue, within the year-to-date window (early January).
    $previousInvoice = Invoice::factory()
        ->for($previousPeriod)
        ->for($customer)
        ->paid()
        ->create(['issue_date' => Carbon::create(2023, 1, 1)]);

    InvoiceItem::factory()
        ->for($previousInvoice)
        ->create(['price' => 1000, 'quantity' => 1]);

    // Current year revenue (higher).
    $currentInvoice = Invoice::factory()
        ->for($currentPeriod)
        ->for($customer)
        ->paid()
        ->create(['issue_date' => Carbon::create(2024, 1, 1)]);

    InvoiceItem::factory()
        ->for($currentInvoice)
        ->create(['price' => 1500, 'quantity' => 1]);

    Livewire::test(StatsOverview::class, ['selectedPeriodId' => $currentPeriod->id])
        ->assertSee('+50.0% from last year');
});

it('excludes previous-year revenue created after the year-to-date cutoff', function () {
    $currentPeriod = AccountingPeriod::factory()->create(['year' => 2024]);
    $previousPeriod = AccountingPeriod::factory()->create(['year' => 2023]);
    $customer = Customer::factory()->create();

    // Previous year revenue issued after today's day-of-year, so it must not
    // count toward the comparison baseline.
    $afterCutoff = Carbon::create(2023, 1, 1)->addDays(now()->dayOfYear);

    $previousInvoice = Invoice::factory()
        ->for($previousPeriod)
        ->for($customer)
        ->paid()
        ->create(['issue_date' => $afterCutoff]);

    InvoiceItem::factory()
        ->for($previousInvoice)
        ->create(['price' => 1000, 'quantity' => 1]);

    $currentInvoice = Invoice::factory()
        ->for($currentPeriod)
        ->for($customer)
        ->paid()
        ->create(['issue_date' => Carbon::create(2024, 1, 1)]);

    InvoiceItem::factory()
        ->for($currentInvoice)
        ->create(['price' => 1500, 'quantity' => 1]);

    Livewire::test(StatsOverview::class, ['selectedPeriodId' => $currentPeriod->id])
        ->assertSee('First year')
        ->assertDontSee('from last year');
});

it('counts paid and issued invoices in total revenue', function () {
    $period = AccountingPeriod::factory()->create(['year' => 2024]);
    $customer = Customer::factory()->create();

    $paid = Invoice::factory()->for($period)->for($customer)->paid()->create();
    InvoiceItem::factory()->for($paid)->create(['price' => 1000, 'quantity' => 1]);

    $issued = Invoice::factory()->for($period)->for($customer)->issued()->create();
    InvoiceItem::factory()->for($issued)->create(['price' => 500, 'quantity' => 1]);

    Livewire::test(StatsOverview::class, ['selectedPeriodId' => $period->id])
        ->assertSee('Total Revenue')
        ->assertSee('€1,500.00');
});

it('excludes the payout amount from total revenue', function () {
    $period = AccountingPeriod::factory()->create(['year' => 2024]);
    $customer = Customer::factory()->create();

    $invoice = Invoice::factory()
        ->for($period)
        ->for($customer)
        ->paid()
        ->withPayout(500)
        ->create();

    InvoiceItem::factory()
        ->for($invoice)
        ->create(['price' => 1000, 'quantity' => 2]);

    // Total is €2,000 but €500 is paid forward, so revenue is €1,500.
    Livewire::test(StatsOverview::class, ['selectedPeriodId' => $period->id])
        ->assertSee('Total Revenue')
        ->assertSee('€1,500.00')
        ->assertDontSee('€2,000.00');
});

it('does not deduct the payout amount from outstanding or draft value', function () {
    $period = AccountingPeriod::factory()->create(['year' => 2024]);
    $customer = Customer::factory()->create();

    $issued = Invoice::factory()
        ->for($period)
        ->for($customer)
        ->issued()
        ->withPayout(200)
        ->create();
    InvoiceItem::factory()->for($issued)->create(['price' => 500, 'quantity' => 1]);

    $draft = Invoice::factory()
        ->for($period)
        ->for($customer)
        ->withPayout(100)
        ->create(['status' => InvoiceStatus::DRAFT]);
    InvoiceItem::factory()->for($draft)->create(['price' => 250, 'quantity' => 1]);

    // Outstanding/Draft are receivables and pipeline, not revenue: gross totals.
    Livewire::test(StatsOverview::class, ['selectedPeriodId' => $period->id])
        ->assertSee('Outstanding Amount')
        ->assertSee('€500.00')
        ->assertSee('Draft Value')
        ->assertSee('€250.00');
});

it('deducts the payout amount from top customer revenue', function () {
    $period = AccountingPeriod::factory()->create(['year' => 2024]);
    $customer = Customer::factory()->create(['name' => 'Pass Through Customer']);

    $invoice = Invoice::factory()
        ->for($period)
        ->for($customer)
        ->paid()
        ->withPayout(1000)
        ->create();

    InvoiceItem::factory()->for($invoice)->create(['price' => 5000, 'quantity' => 1]);

    // Filament money column renders with the app locale (sl): 4.000,00 €.
    Livewire::test(TopCustomers::class, ['selectedPeriodId' => $period->id])
        ->assertSee('Pass Through Customer')
        ->assertSee('4.000,00')
        ->assertDontSee('5.000,00');
});

it('does not show a year-over-year comparison for outstanding amount or draft value', function () {
    $period = AccountingPeriod::factory()->create(['year' => 2024]);
    $customer = Customer::factory()->create();

    $issued = Invoice::factory()->for($period)->for($customer)->issued()->create();
    InvoiceItem::factory()->for($issued)->create(['price' => 500, 'quantity' => 1]);

    $draft = Invoice::factory()
        ->for($period)
        ->for($customer)
        ->create(['status' => InvoiceStatus::DRAFT]);
    InvoiceItem::factory()->for($draft)->create(['price' => 250, 'quantity' => 1]);

    // No previous period exists, so the revenue card shows "First year" rather
    // than a comparison — "from last year" must not appear at all.
    Livewire::test(StatsOverview::class, ['selectedPeriodId' => $period->id])
        ->assertSee('Outstanding Amount')
        ->assertSee('Draft Value')
        ->assertDontSee('from last year');
});

it('displays revenue trends chart', function () {
    $period = AccountingPeriod::factory()->create(['year' => 2024]);

    Livewire::test(RevenueTrends::class, ['selectedPeriodId' => $period->id])
        ->assertSee('Revenue Trends');
});

it('displays latest issued invoices', function () {
    $period = AccountingPeriod::factory()->create(['year' => 2024]);
    $customer = Customer::factory()->create(['name' => 'Test Customer']);

    $invoice = Invoice::factory()
        ->for($period)
        ->for($customer)
        ->issued(123)
        ->create();

    InvoiceItem::factory()
        ->for($invoice)
        ->create(['price' => 1000, 'quantity' => 1]);

    Livewire::test(LatestIssuedInvoices::class, ['selectedPeriodId' => $period->id])
        ->assertSee('Unpaid Invoices & Drafts')
        ->assertSee('Test Customer')
        ->assertSee('123');
});

it('filters latest issued invoices by customer and status', function () {
    $period = AccountingPeriod::factory()->create(['year' => 2024]);
    $customer = Customer::factory()->create(['name' => 'Filtered Customer']);
    $otherCustomer = Customer::factory()->create(['name' => 'Other Customer']);

    $matchingInvoice = Invoice::factory()
        ->for($period)
        ->for($customer)
        ->issued(123)
        ->create();

    $otherCustomerInvoice = Invoice::factory()
        ->for($period)
        ->for($otherCustomer)
        ->issued(124)
        ->create();

    $otherStatusInvoice = Invoice::factory()
        ->for($period)
        ->for($customer)
        ->create(['status' => InvoiceStatus::DRAFT]);

    Livewire::test(LatestIssuedInvoices::class, ['selectedPeriodId' => $period->id])
        ->filterTable('customer', $customer->id)
        ->filterTable('status', InvoiceStatus::ISSUED->value)
        ->assertCanSeeTableRecords([$matchingInvoice])
        ->assertCanNotSeeTableRecords([
            $otherCustomerInvoice,
            $otherStatusInvoice,
        ]);
});

it('displays top customers by revenue', function () {
    $period = AccountingPeriod::factory()->create(['year' => 2024]);
    $customer1 = Customer::factory()->create(['name' => 'High Value Customer']);
    $customer2 = Customer::factory()->create(['name' => 'Low Value Customer']);

    // High value customer
    $invoice1 = Invoice::factory()
        ->for($period)
        ->for($customer1)
        ->paid()
        ->create();

    InvoiceItem::factory()
        ->for($invoice1)
        ->create(['price' => 5000, 'quantity' => 1]);

    // Low value customer
    $invoice2 = Invoice::factory()
        ->for($period)
        ->for($customer2)
        ->paid()
        ->create();

    InvoiceItem::factory()
        ->for($invoice2)
        ->create(['price' => 500, 'quantity' => 1]);

    Livewire::test(TopCustomers::class, ['selectedPeriodId' => $period->id])
        ->assertSee('Top Customers by Revenue')
        ->assertSee('High Value Customer')
        ->assertSee('Low Value Customer');
});

it('calculates invoice total correctly', function () {
    $period = AccountingPeriod::factory()->create(['year' => 2024]);
    $customer = Customer::factory()->create();

    $invoice = Invoice::factory()
        ->for($period)
        ->for($customer)
        ->create();

    InvoiceItem::factory()->for($invoice)->create(['price' => 100, 'quantity' => 2]);
    InvoiceItem::factory()->for($invoice)->create(['price' => 50, 'quantity' => 3]);

    expect($invoice->total())->toBe(350.0);
});

it('calculates net revenue as total minus payout', function () {
    $invoice = Invoice::factory()->withPayout(50)->create();

    InvoiceItem::factory()->for($invoice)->create(['price' => 100, 'quantity' => 2]);
    InvoiceItem::factory()->for($invoice)->create(['price' => 50, 'quantity' => 3]);

    $invoice->load('items');

    expect($invoice->total())->toBe(350.0)
        ->and($invoice->netRevenue())->toBe(300.0);
});

it('treats a null payout as zero net revenue deduction', function () {
    $invoice = Invoice::factory()->create(['payout_amount' => null]);

    InvoiceItem::factory()->for($invoice)->create(['price' => 100, 'quantity' => 1]);

    $invoice->load('items');

    expect($invoice->netRevenue())->toBe(100.0);
});

it('updates widgets when accounting period changes', function () {
    $period1 = AccountingPeriod::factory()->create(['year' => 2024]);
    $period2 = AccountingPeriod::factory()->create(['year' => 2023]);

    Livewire::test(AccountingPeriodSelector::class)
        ->set('selectedPeriodId', $period1->id)
        ->assertDispatched('accounting-period-changed', periodId: $period1->id);
});
