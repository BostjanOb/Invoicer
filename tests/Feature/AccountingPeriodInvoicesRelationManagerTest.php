<?php

use App\Filament\Resources\AccountingPeriods\Pages\ViewAccountingPeriod;
use App\Filament\Resources\AccountingPeriods\RelationManagers\InvoicesRelationManager;
use App\Models\AccountingPeriod;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    $this->period = AccountingPeriod::factory()->create(['year' => now()->year]);
});

it('shows the total column and hides payment deadline and service text', function () {
    $invoice = Invoice::factory()->for($this->period, 'accountingPeriod')->create([
        'service_text' => 'Hidden service text',
    ]);
    InvoiceItem::factory()->for($invoice)->create([
        'price' => 100,
        'quantity' => 2,
    ]);

    Livewire::test(InvoicesRelationManager::class, [
        'ownerRecord' => $this->period,
        'pageClass' => ViewAccountingPeriod::class,
    ])
        ->assertCanSeeTableRecords([$invoice])
        ->assertCanRenderTableColumn('total')
        ->assertTableColumnDoesNotExist('payment_deadline')
        ->assertTableColumnDoesNotExist('service_text');
});
