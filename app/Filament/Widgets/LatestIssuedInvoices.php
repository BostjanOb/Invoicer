<?php

namespace App\Filament\Widgets;

use App\Enums\InvoiceStatus;
use App\Models\AccountingPeriod;
use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Livewire\Attributes\On;

class LatestIssuedInvoices extends BaseWidget
{
    protected static ?string $heading = 'Latest Issued Invoices';

    protected int|string|array $columnSpan = 'full';

    public ?int $selectedPeriodId = null;

    #[On('accounting-period-changed')]
    public function updatePeriod(int $periodId): void
    {
        $this->selectedPeriodId = $periodId;
    }

    public function mount(): void
    {
        $defaultPeriod = AccountingPeriod::query()
            ->where('is_closed', false)
            ->latest('year')
            ->first();

        $this->selectedPeriodId = session('selected_accounting_period_id', $defaultPeriod?->id);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Invoice::query()
                    ->where('accounting_period_id', $this->selectedPeriodId)
                    ->where('status', InvoiceStatus::ISSUED)
                    ->with(['customer', 'items'])
                    ->latest('issue_date')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('number')
                    ->label('Invoice #')
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('issue_date')
                    ->label('Issue Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('total')
                    ->label('Amount')
                    ->money('EUR')
                    ->getStateUsing(fn (Invoice $record): float => $record->total())
                    ->sortable(),

                TextColumn::make('days_outstanding')
                    ->label('Days Outstanding')
                    ->getStateUsing(function (Invoice $record): int {
                        return $record->issue_date?->diffInDays(now()) ?? 0;
                    })
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state > 60 => 'danger',
                        $state > 30 => 'warning',
                        default => 'success',
                    }),
            ])
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->url(fn (Invoice $record): string => route('filament.admin.resources.invoices.view', ['record' => $record]))
                    ->icon('heroicon-m-eye'),
            ]);
    }
}
