<?php

use App\Enums\InvoiceStatus;
use App\Filament\Resources\Invoices\Pages\CreateInvoice;
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

it('creates a draft invoice without a payment deadline', function () {
    $period = AccountingPeriod::factory()->create(['year' => 2024]);
    $customer = Customer::factory()->create();

    Livewire::test(CreateInvoice::class)
        ->fillForm([
            'customer_id' => $customer->id,
            'accounting_period_id' => $period->id,
            'payment_deadline' => null,
            'items' => [
                ['title' => 'Consulting', 'quantity' => 1, 'price' => 100],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $this->assertDatabaseHas(Invoice::class, [
        'customer_id' => $customer->id,
        'accounting_period_id' => $period->id,
        'payment_deadline' => null,
        'status' => InvoiceStatus::DRAFT->value,
    ]);
});

it('stores the payout amount on the invoice', function () {
    $period = AccountingPeriod::factory()->create(['year' => 2024]);
    $customer = Customer::factory()->create();

    Livewire::test(CreateInvoice::class)
        ->fillForm([
            'customer_id' => $customer->id,
            'accounting_period_id' => $period->id,
            'payout_amount' => 250,
            'items' => [
                ['title' => 'Subcontracted work', 'quantity' => 1, 'price' => 1000],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $this->assertDatabaseHas(Invoice::class, [
        'customer_id' => $customer->id,
        'accounting_period_id' => $period->id,
        'payout_amount' => 250,
    ]);
});
