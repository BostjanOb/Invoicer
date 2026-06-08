<?php

use App\Enums\InvoiceStatus;
use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Models\AccountingPeriod;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('filters invoices by customer status and year period', function () {
    $customer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();
    $period = AccountingPeriod::factory()->create(['year' => 2025]);
    $otherPeriod = AccountingPeriod::factory()->create(['year' => 2024]);

    $matchingInvoice = Invoice::factory()
        ->for($customer)
        ->for($period, 'accountingPeriod')
        ->paid()
        ->create();

    $otherCustomerInvoice = Invoice::factory()
        ->for($otherCustomer)
        ->for($period, 'accountingPeriod')
        ->paid()
        ->create();

    $otherStatusInvoice = Invoice::factory()
        ->for($customer)
        ->for($period, 'accountingPeriod')
        ->issued()
        ->create();

    $otherPeriodInvoice = Invoice::factory()
        ->for($customer)
        ->for($otherPeriod, 'accountingPeriod')
        ->paid()
        ->create();

    Livewire::test(ListInvoices::class)
        ->filterTable('customer', $customer->id)
        ->filterTable('status', InvoiceStatus::PAID->value)
        ->filterTable('accounting_period_id', $period->id)
        ->assertCanSeeTableRecords([$matchingInvoice])
        ->assertCanNotSeeTableRecords([
            $otherCustomerInvoice,
            $otherStatusInvoice,
            $otherPeriodInvoice,
        ]);
});
