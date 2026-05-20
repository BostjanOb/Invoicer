<?php

use App\Filament\Resources\Invoices\Pages\ViewInvoice;
use App\Models\AccountingPeriod;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('shows the accounting period year on the view page', function () {
    $period = AccountingPeriod::factory()->create(['year' => 2024]);
    $invoice = Invoice::factory()->create(['accounting_period_id' => $period->id]);

    Livewire::test(ViewInvoice::class, ['record' => $invoice->id])
        ->assertSee('Accounting Period')
        ->assertSee('2024');
});

it('shows the payout and net revenue when a payout is set', function () {
    $invoice = Invoice::factory()->withPayout(400)->create();
    InvoiceItem::factory()->for($invoice)->create(['price' => 1000, 'quantity' => 1]);

    // Filament money entries render with the app locale (sl): 600,00 €.
    Livewire::test(ViewInvoice::class, ['record' => $invoice->id])
        ->assertSee('Payout')
        ->assertSee('Net Revenue')
        ->assertSee('600,00');
});

it('hides the payout and net revenue when there is no payout', function () {
    $invoice = Invoice::factory()->create(['payout_amount' => 0]);
    InvoiceItem::factory()->for($invoice)->create(['price' => 1000, 'quantity' => 1]);

    Livewire::test(ViewInvoice::class, ['record' => $invoice->id])
        ->assertSee('Total Amount')
        ->assertDontSee('Net Revenue');
});
