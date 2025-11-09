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

it('displays year-over-year comparison', function () {
    $currentPeriod = AccountingPeriod::factory()->create(['year' => 2024]);
    $previousPeriod = AccountingPeriod::factory()->create(['year' => 2023]);
    $customer = Customer::factory()->create();

    // Previous year revenue
    $previousInvoice = Invoice::factory()
        ->for($previousPeriod)
        ->for($customer)
        ->paid()
        ->create();

    InvoiceItem::factory()
        ->for($previousInvoice)
        ->create(['price' => 1000, 'quantity' => 1]);

    // Current year revenue (higher)
    $currentInvoice = Invoice::factory()
        ->for($currentPeriod)
        ->for($customer)
        ->paid()
        ->create();

    InvoiceItem::factory()
        ->for($currentInvoice)
        ->create(['price' => 1500, 'quantity' => 1]);

    Livewire::test(StatsOverview::class, ['selectedPeriodId' => $currentPeriod->id])
        ->assertSee('+50.0% from last year');
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
        ->assertSee('Latest Issued Invoices')
        ->assertSee('Test Customer')
        ->assertSee('123');
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

it('updates widgets when accounting period changes', function () {
    $period1 = AccountingPeriod::factory()->create(['year' => 2024]);
    $period2 = AccountingPeriod::factory()->create(['year' => 2023]);

    Livewire::test(AccountingPeriodSelector::class)
        ->set('selectedPeriodId', $period1->id)
        ->assertDispatched('accounting-period-changed', periodId: $period1->id);
});
