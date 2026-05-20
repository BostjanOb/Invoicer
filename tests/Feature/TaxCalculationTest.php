<?php

use App\Enums\InvoiceStatus;
use App\Filament\Resources\AccountingPeriods\Pages\ViewAccountingPeriod;
use App\Filament\Resources\Invoices\Pages\ViewInvoice;
use App\Models\AccountingPeriod;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Taxes\FlatRateTaxCalculator;
use App\Taxes\ProgressiveTaxCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('calculates flat rate tax of 4 percent on invoice total', function () {
    $period = AccountingPeriod::factory()->create(['year' => 2024, 'tax_calculator' => FlatRateTaxCalculator::class]);
    $invoice = Invoice::factory()->for($period)->create();
    InvoiceItem::factory()->for($invoice)->create(['price' => 1000, 'quantity' => 5]); // Total = 5000

    $calculator = new FlatRateTaxCalculator;
    expect($calculator->calculate($invoice))->toBe(200.0);
});

it('calculates progressive tax correctly across brackets', function () {
    $period = AccountingPeriod::factory()->create(['year' => 2025, 'tax_calculator' => ProgressiveTaxCalculator::class]);
    $customer = Customer::factory()->create();

    // Invoice 1: 10,000. Cumulative income goes from 0 to 10,000. All < 12,500. Tax at 4% = 400.
    $invoice1 = Invoice::factory()->for($period)->for($customer)->issued(1)->create();
    InvoiceItem::factory()->for($invoice1)->create(['price' => 10000, 'quantity' => 1]);

    // Invoice 2: 5,000. Cumulative income goes from 10,000 to 15,000.
    // 2,500 (up to 12,500) taxed at 4% = 100.
    // 2,500 (above 12,500) taxed at 12% = 300.
    // Total tax = 400.
    $invoice2 = Invoice::factory()->for($period)->for($customer)->issued(2)->create();
    InvoiceItem::factory()->for($invoice2)->create(['price' => 5000, 'quantity' => 1]);

    // Invoice 3: 20,000. Cumulative income goes from 15,000 to 35,000.
    // 15,000 (up to 30,000) taxed at 12% = 1800.
    // 5,000 (above 30,000) taxed at 30% = 1500.
    // Total tax = 3300.
    $invoice3 = Invoice::factory()->for($period)->for($customer)->issued(3)->create();
    InvoiceItem::factory()->for($invoice3)->create(['price' => 20000, 'quantity' => 1]);

    // Draft Invoice: 1,000. Should be calculated at the end of the issued/paid sequence (from 35,000 to 36,000).
    // All of it above 30,000, so taxed at 30% = 300.
    $draftInvoice = Invoice::factory()->for($period)->for($customer)->create(['status' => InvoiceStatus::DRAFT, 'number' => null]);
    InvoiceItem::factory()->for($draftInvoice)->create(['price' => 1000, 'quantity' => 1]);

    $calculator = new ProgressiveTaxCalculator;

    expect($calculator->calculate($invoice1))->toBe(400.0)
        ->and($calculator->calculate($invoice2))->toBe(400.0)
        ->and($calculator->calculate($invoice3))->toBe(3300.0)
        ->and($calculator->calculate($draftInvoice))->toBe(300.0);
});

it('resolves correct tax calculator based on year fallback', function () {
    $period2024 = AccountingPeriod::factory()->create(['year' => 2024]);
    $period2025 = AccountingPeriod::factory()->create(['year' => 2025]);
    $period2026 = AccountingPeriod::factory()->create(['year' => 2026]);
    $period2027 = AccountingPeriod::factory()->create(['year' => 2027]);

    expect($period2024->getTaxCalculator())->toBeInstanceOf(FlatRateTaxCalculator::class)
        ->and($period2025->getTaxCalculator())->toBeInstanceOf(ProgressiveTaxCalculator::class)
        ->and($period2026->getTaxCalculator())->toBeInstanceOf(ProgressiveTaxCalculator::class)
        ->and($period2027->getTaxCalculator())->toBeInstanceOf(FlatRateTaxCalculator::class);
});

it('resolves explicitly configured tax calculator', function () {
    $period = AccountingPeriod::factory()->create(['year' => 2025, 'tax_calculator' => FlatRateTaxCalculator::class]);
    expect($period->getTaxCalculator())->toBeInstanceOf(FlatRateTaxCalculator::class);
});

it('calculates total tax that should be paid for period', function () {
    $period = AccountingPeriod::factory()->create(['year' => 2024, 'tax_calculator' => FlatRateTaxCalculator::class]);
    $customer = Customer::factory()->create();

    // Invoice 1: 5,000 (issued). Tax = 200.
    $invoice1 = Invoice::factory()->for($period)->for($customer)->issued(1)->create();
    InvoiceItem::factory()->for($invoice1)->create(['price' => 5000, 'quantity' => 1]);

    // Invoice 2: 3,000 (paid). Tax = 120.
    $invoice2 = Invoice::factory()->for($period)->for($customer)->paid()->create(['number' => 2]);
    InvoiceItem::factory()->for($invoice2)->create(['price' => 3000, 'quantity' => 1]);

    // Invoice 3: 1,000 (draft). Tax = 40, but ignored for the period total.
    $invoice3 = Invoice::factory()->for($period)->for($customer)->create(['status' => InvoiceStatus::DRAFT, 'number' => null]);
    InvoiceItem::factory()->for($invoice3)->create(['price' => 1000, 'quantity' => 1]);

    expect($period->taxShouldBePaid())->toBe(320.0);
});

it('displays tax details on accounting period view page', function () {
    $period = AccountingPeriod::factory()->create([
        'year' => 2025,
        'monthly_tax_paid' => 100.00, // Year prepayment = 1,200.00
        'tax_calculator' => ProgressiveTaxCalculator::class,
    ]);
    $customer = Customer::factory()->create();

    // 15,000 total. Tax under progressive is:
    // 12,500 * 0.04 = 500
    // 2,500 * 0.12 = 300
    // Total should be paid = 800.
    // Difference = 800 - 1200 = -400.
    $invoice = Invoice::factory()->for($period)->for($customer)->issued(1)->create();
    InvoiceItem::factory()->for($invoice)->create(['price' => 15000, 'quantity' => 1]);

    Livewire::test(ViewAccountingPeriod::class, ['record' => $period->id])
        ->assertSee('Tax Details')
        ->assertSee('Monthly Tax Paid')
        ->assertSee('100,00')
        ->assertSee('Tax Paid This Year')
        ->assertSee('1.200,00')
        ->assertSee('Tax Should Be Paid')
        ->assertSee('800,00')
        ->assertSee('Tax Difference')
        ->assertSee('-400,00')
        ->assertSee('you will get returned next year');
});

it('displays positive tax difference on accounting period view page', function () {
    $period = AccountingPeriod::factory()->create([
        'year' => 2025,
        'monthly_tax_paid' => 50.00, // Year prepayment = 600.00
        'tax_calculator' => ProgressiveTaxCalculator::class,
    ]);
    $customer = Customer::factory()->create();

    // 15,000 total. Tax = 800.
    // Difference = 800 - 600 = +200.
    $invoice = Invoice::factory()->for($period)->for($customer)->issued(1)->create();
    InvoiceItem::factory()->for($invoice)->create(['price' => 15000, 'quantity' => 1]);

    Livewire::test(ViewAccountingPeriod::class, ['record' => $period->id])
        ->assertSee('Tax Difference')
        ->assertSee('+200,00')
        ->assertSee('you will need to pay more');
});

it('displays tax on single invoice view page', function () {
    $period = AccountingPeriod::factory()->create(['year' => 2024, 'tax_calculator' => FlatRateTaxCalculator::class]);
    $invoice = Invoice::factory()->for($period)->create();
    InvoiceItem::factory()->for($invoice)->create(['price' => 1000, 'quantity' => 5]); // Total = 5,000, Tax = 200

    Livewire::test(ViewInvoice::class, ['record' => $invoice->id])
        ->assertSee('Total Amount')
        ->assertSee('Tax')
        ->assertSee('200,00');
});
