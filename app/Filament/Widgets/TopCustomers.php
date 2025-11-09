<?php

namespace App\Filament\Widgets;

use App\Enums\InvoiceStatus;
use App\Models\AccountingPeriod;
use App\Models\Customer;
use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;

class TopCustomers extends BaseWidget
{
    protected static ?string $heading = 'Top Customers by Revenue';

    protected int|string|array $columnSpan = 'full';

    public ?int $selectedPeriodId = null;

    protected static ?int $sort = 5;

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
                Customer::query()
                    ->whereHas('invoices', function (Builder $query) {
                        $query->where('accounting_period_id', $this->selectedPeriodId)
                            ->where('status', InvoiceStatus::PAID);
                    })
                    ->withCount([
                        'invoices as paid_invoices_count' => function (Builder $query) {
                            $query->where('accounting_period_id', $this->selectedPeriodId)
                                ->where('status', InvoiceStatus::PAID);
                        },
                    ])
                    ->with([
                        'invoices' => function ($query) {
                            $query->where('accounting_period_id', $this->selectedPeriodId)
                                ->where('status', InvoiceStatus::PAID)
                                ->with('items');
                        },
                    ])
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('total_revenue')
                    ->label('Total Revenue')
                    ->money('EUR')
                    ->getStateUsing(function (Customer $record): float {
                        return $record->invoices->sum(fn (Invoice $invoice) => $invoice->total());
                    }),

                TextColumn::make('paid_invoices_count')
                    ->label('Invoice Count')
                    ->sortable(),

                TextColumn::make('average_invoice')
                    ->label('Avg Invoice')
                    ->money('EUR')
                    ->getStateUsing(function (Customer $record): float {
                        $total = $record->invoices->sum(fn (Invoice $invoice) => $invoice->total());
                        $count = $record->paid_invoices_count;

                        return $count > 0 ? $total / $count : 0;
                    }),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    ->url(fn (Customer $record): string => route('filament.admin.resources.customers.view', ['record' => $record]))
                    ->icon('heroicon-m-eye'),
            ])
            ->defaultSort('paid_invoices_count', 'desc')
            ->paginated([5, 10, 25]);
    }
}
