<?php

use App\Enums\InvoiceStatus;
use App\Filament\Resources\Invoices\Pages\ViewInvoice;
use App\Models\AccountingPeriod;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    // Create accounting periods for current year and adjacent years
    $this->currentYear = now()->year;
    $this->lastYear = $this->currentYear - 1;
    $this->oldYear = $this->currentYear - 2;

    $this->currentPeriod = AccountingPeriod::factory()->create(['year' => $this->currentYear]);
    $this->lastYearPeriod = AccountingPeriod::factory()->create(['year' => $this->lastYear]);
    $this->oldYearPeriod = AccountingPeriod::factory()->create(['year' => $this->oldYear]);
});

it('issues first invoice with number 1 when no other invoices exist', function () {
    $invoice = Invoice::factory()->for($this->currentPeriod, 'accountingPeriod')->create();

    Livewire::test(ViewInvoice::class, ['record' => $invoice->id])
        ->callAction('issue');

    $this->assertDatabaseHas('invoices', [
        'id' => $invoice->id,
        'number' => 1,
        'accounting_period_id' => $this->currentPeriod->id,
        'status' => InvoiceStatus::ISSUED,
    ]);

    expect($invoice->fresh())
        ->number->toBe(1)
        ->status->toBe(InvoiceStatus::ISSUED)
        ->accountingPeriod->year->toBe($this->currentYear)
        ->issue_date->not->toBeNull();
});

it('generates sequential number when issued invoices already exist', function () {
    Invoice::factory()->for($this->currentPeriod, 'accountingPeriod')->issued(1)->create();
    Invoice::factory()->for($this->currentPeriod, 'accountingPeriod')->issued(2)->create();

    $draftInvoice = Invoice::factory()->for($this->currentPeriod, 'accountingPeriod')->create();

    Livewire::test(ViewInvoice::class, ['record' => $draftInvoice->id])
        ->callAction('issue');

    $this->assertDatabaseHas('invoices', [
        'id' => $draftInvoice->id,
        'number' => 3,
        'accounting_period_id' => $this->currentPeriod->id,
        'status' => InvoiceStatus::ISSUED,
    ]);

    expect($draftInvoice->fresh()->number)->toBe(3);
});

it('ignores draft invoices when calculating sequential number', function () {
    Invoice::factory()->for($this->currentPeriod, 'accountingPeriod')->issued(1)->create();
    Invoice::factory()->for($this->currentPeriod, 'accountingPeriod')->count(5)->create(); // 5 draft invoices
    Invoice::factory()->for($this->currentPeriod, 'accountingPeriod')->issued(2)->create();

    $draftInvoice = Invoice::factory()->for($this->currentPeriod, 'accountingPeriod')->create();

    Livewire::test(ViewInvoice::class, ['record' => $draftInvoice->id])
        ->callAction('issue');

    $this->assertDatabaseHas('invoices', [
        'id' => $draftInvoice->id,
        'number' => 3,
        'status' => InvoiceStatus::ISSUED,
    ]);
});

it('includes paid invoices when calculating sequential number', function () {
    Invoice::factory()->for($this->currentPeriod, 'accountingPeriod')->issued(1)->create();
    Invoice::factory()->for($this->currentPeriod, 'accountingPeriod')->paid(2)->create();
    Invoice::factory()->for($this->currentPeriod, 'accountingPeriod')->paid(3)->create();

    $draftInvoice = Invoice::factory()->for($this->currentPeriod, 'accountingPeriod')->create();

    Livewire::test(ViewInvoice::class, ['record' => $draftInvoice->id])
        ->callAction('issue');

    $this->assertDatabaseHas('invoices', [
        'id' => $draftInvoice->id,
        'number' => 4,
        'status' => InvoiceStatus::ISSUED,
    ]);
});

it('restarts sequential numbering for different accounting periods', function () {
    // Create invoices from last year
    Invoice::factory()->for($this->lastYearPeriod, 'accountingPeriod')->issued(1)->create();
    Invoice::factory()->for($this->lastYearPeriod, 'accountingPeriod')->issued(2)->create();
    Invoice::factory()->for($this->lastYearPeriod, 'accountingPeriod')->paid(3)->create();

    // Create invoice for current year
    $draftInvoice = Invoice::factory()->for($this->currentPeriod, 'accountingPeriod')->create();

    Livewire::test(ViewInvoice::class, ['record' => $draftInvoice->id])
        ->callAction('issue');

    $this->assertDatabaseHas('invoices', [
        'id' => $draftInvoice->id,
        'number' => 1,
        'accounting_period_id' => $this->currentPeriod->id,
        'status' => InvoiceStatus::ISSUED,
    ]);
});

it('correctly calculates sequential number with mixed statuses in same period', function () {
    // Create mix of invoices in current period
    Invoice::factory()->for($this->currentPeriod, 'accountingPeriod')->issued(1)->create();
    Invoice::factory()->for($this->currentPeriod, 'accountingPeriod')->create(); // draft
    Invoice::factory()->for($this->currentPeriod, 'accountingPeriod')->paid(2)->create();
    Invoice::factory()->for($this->currentPeriod, 'accountingPeriod')->create(); // draft
    Invoice::factory()->for($this->currentPeriod, 'accountingPeriod')->issued(3)->create();

    $draftInvoice = Invoice::factory()->for($this->currentPeriod, 'accountingPeriod')->create();

    Livewire::test(ViewInvoice::class, ['record' => $draftInvoice->id])
        ->callAction('issue');

    $this->assertDatabaseHas('invoices', [
        'id' => $draftInvoice->id,
        'number' => 4,
        'accounting_period_id' => $this->currentPeriod->id,
        'status' => InvoiceStatus::ISSUED,
    ]);
});

it('correctly handles invoices across multiple accounting periods', function () {
    // Last year invoices
    Invoice::factory()->for($this->lastYearPeriod, 'accountingPeriod')->issued(1)->create();
    Invoice::factory()->for($this->lastYearPeriod, 'accountingPeriod')->paid(2)->create();

    // Current year invoices
    Invoice::factory()->for($this->currentPeriod, 'accountingPeriod')->issued(1)->create();
    Invoice::factory()->for($this->currentPeriod, 'accountingPeriod')->paid(2)->create();

    // Draft invoice from last year that will be issued with last year's accounting period
    $oldDraftInvoice = Invoice::factory()->for($this->lastYearPeriod, 'accountingPeriod')->create();

    Livewire::test(ViewInvoice::class, ['record' => $oldDraftInvoice->id])
        ->callAction('issue');

    // Should get number 3 in last year's sequence and keep last year's accounting period
    $this->assertDatabaseHas('invoices', [
        'id' => $oldDraftInvoice->id,
        'number' => 3,
        'accounting_period_id' => $this->lastYearPeriod->id,
        'status' => InvoiceStatus::ISSUED,
    ]);
});

it('preserves accounting period when issuing old draft', function () {
    $draftInvoice = Invoice::factory()->for($this->oldYearPeriod, 'accountingPeriod')->create();

    Livewire::test(ViewInvoice::class, ['record' => $draftInvoice->id])
        ->callAction('issue');

    expect($draftInvoice->fresh())
        ->accountingPeriod->year->toBe($this->oldYear)
        ->number->toBe(1);
});

it('is only visible for draft invoices', function () {
    $draftInvoice = Invoice::factory()->for($this->currentPeriod, 'accountingPeriod')->create();
    $issuedInvoice = Invoice::factory()->for($this->currentPeriod, 'accountingPeriod')->issued(1)->create();
    $paidInvoice = Invoice::factory()->for($this->currentPeriod, 'accountingPeriod')->paid(2)->create();

    Livewire::test(ViewInvoice::class, ['record' => $draftInvoice->id])
        ->assertActionVisible('issue');

    Livewire::test(ViewInvoice::class, ['record' => $issuedInvoice->id])
        ->assertActionHidden('issue');

    Livewire::test(ViewInvoice::class, ['record' => $paidInvoice->id])
        ->assertActionHidden('issue');
});

it('requires confirmation before issuing', function () {
    $invoice = Invoice::factory()->for($this->currentPeriod, 'accountingPeriod')->create();

    Livewire::test(ViewInvoice::class, ['record' => $invoice->id])
        ->call('mountAction', 'issue')
        ->assertSee('Issue Invoice');
});

it('sends success notification after issuing', function () {
    $invoice = Invoice::factory()->for($this->currentPeriod, 'accountingPeriod')->create();

    Livewire::test(ViewInvoice::class, ['record' => $invoice->id])
        ->callAction('issue')
        ->assertNotified();
});
