<?php

use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Filament\Resources\Customers\RelationManagers\InvoicesRelationManager;
use App\Models\AccountingPeriod;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Number;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    $this->customer = Customer::factory()->create();
});

it('shows the customer invoices with the total column', function () {
    $invoice = Invoice::factory()->for($this->customer)->create();
    InvoiceItem::factory()->for($invoice)->create([
        'price' => 100,
        'quantity' => 2,
    ]);

    $otherInvoice = Invoice::factory()->for(Customer::factory())->create();

    Livewire::test(InvoicesRelationManager::class, [
        'ownerRecord' => $this->customer,
        'pageClass' => ViewCustomer::class,
    ])
        ->assertCanSeeTableRecords([$invoice])
        ->assertCanNotSeeTableRecords([$otherInvoice])
        ->assertCanRenderTableColumn('total')
        ->assertCanRenderTableColumn('accountingPeriod.year');
});

it('summarizes the total across the customer invoices', function () {
    $first = Invoice::factory()->for($this->customer)->create();
    InvoiceItem::factory()->for($first)->create(['price' => 100, 'quantity' => 2]);

    $second = Invoice::factory()->for($this->customer)->create();
    InvoiceItem::factory()->for($second)->create(['price' => 50, 'quantity' => 3]);

    Livewire::test(InvoicesRelationManager::class, [
        'ownerRecord' => $this->customer,
        'pageClass' => ViewCustomer::class,
    ])
        ->assertTableColumnSummarySet('total', 'sum', Number::currency(350, 'EUR'));
});

it('can group the invoices by accounting period', function () {
    $period = AccountingPeriod::factory()->create(['year' => 2025]);
    $invoice = Invoice::factory()
        ->for($this->customer)
        ->for($period, 'accountingPeriod')
        ->create();

    Livewire::test(InvoicesRelationManager::class, [
        'ownerRecord' => $this->customer,
        'pageClass' => ViewCustomer::class,
    ])
        ->set('tableGrouping', 'accountingPeriod.year')
        ->assertCanSeeTableRecords([$invoice]);
});
