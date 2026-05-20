<?php

use App\Filament\Resources\Invoices\Pages\EditInvoice;
use App\Filament\Resources\Invoices\Pages\ViewInvoice;
use App\Models\Invoice;
use App\Models\User;
use Filament\Actions\EditAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('allows editing a draft invoice', function () {
    $invoice = Invoice::factory()->create();

    expect($this->user->can('update', $invoice))->toBeTrue();

    Livewire::test(EditInvoice::class, ['record' => $invoice->id])
        ->assertSuccessful();

    Livewire::test(ViewInvoice::class, ['record' => $invoice->id])
        ->assertActionVisible(EditAction::class);
});

it('forbids editing an issued invoice', function () {
    $invoice = Invoice::factory()->issued(1)->create();

    expect($this->user->can('update', $invoice))->toBeFalse();

    Livewire::test(EditInvoice::class, ['record' => $invoice->id])
        ->assertForbidden();

    Livewire::test(ViewInvoice::class, ['record' => $invoice->id])
        ->assertActionHidden(EditAction::class);
});

it('forbids editing a paid invoice', function () {
    $invoice = Invoice::factory()->paid(1)->create();

    expect($this->user->can('update', $invoice))->toBeFalse();

    Livewire::test(EditInvoice::class, ['record' => $invoice->id])
        ->assertForbidden();

    Livewire::test(ViewInvoice::class, ['record' => $invoice->id])
        ->assertActionHidden(EditAction::class);
});
