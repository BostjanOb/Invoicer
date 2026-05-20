<?php

namespace App\Filament\Resources\AccountingPeriods\Schemas;

use App\Enums\InvoiceStatus;
use App\Models\AccountingPeriod;
use App\Models\Invoice;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AccountingPeriodInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(1)
                    ->schema([
                        Section::make('Accounting Period Details')
                            ->schema([
                                TextEntry::make('year')
                                    ->numeric(
                                        thousandsSeparator: ''
                                    ),
                                IconEntry::make('is_closed')
                                    ->boolean(),
                            ])
                            ->columns(2),

                        Section::make('Amount Summary')
                            ->schema([
                                TextEntry::make('total_amount')
                                    ->label('Total Amount')
                                    ->money('EUR')
                                    ->state(fn (AccountingPeriod $record): float => $record->invoices
                                        ->whereIn('status', [InvoiceStatus::ISSUED, InvoiceStatus::PAID])
                                        ->sum(fn (Invoice $invoice) => $invoice->total())),
                                TextEntry::make('payout_amount')
                                    ->label('Payout Amount')
                                    ->money('EUR')
                                    ->state(fn (AccountingPeriod $record): float => (float) $record->invoices
                                        ->whereIn('status', [InvoiceStatus::ISSUED, InvoiceStatus::PAID])
                                        ->sum('payout_amount')),
                                TextEntry::make('total_revenue')
                                    ->label('Total Revenue')
                                    ->money('EUR')
                                    ->state(fn (AccountingPeriod $record): float => $record->invoices
                                        ->whereIn('status', [InvoiceStatus::ISSUED, InvoiceStatus::PAID])
                                        ->sum(fn (Invoice $invoice) => $invoice->netRevenue())),
                            ])
                            ->columns(3),
                    ]),

                Section::make('Tax Details')
                    ->schema([
                        TextEntry::make('tax_calculator')
                            ->label('Tax Calculator')
                            ->state(fn (AccountingPeriod $record): string => $record->tax_calculator
                                ? (AccountingPeriod::getAvailableCalculators()[$record->tax_calculator] ?? $record->tax_calculator)
                                : '-'
                            ),
                        TextEntry::make('monthly_tax_paid')
                            ->label('Monthly Tax Paid')
                            ->money('EUR'),
                        TextEntry::make('tax_paid_this_year')
                            ->label('Tax Paid This Year')
                            ->money('EUR')
                            ->state(fn (AccountingPeriod $record): float => $record->monthly_tax_paid * 12),
                        TextEntry::make('tax_should_be_paid')
                            ->label('Tax Should Be Paid')
                            ->money('EUR')
                            ->state(fn (AccountingPeriod $record): float => $record->taxShouldBePaid()),
                        TextEntry::make('tax_diff')
                            ->label('Tax Difference')
                            ->state(function (AccountingPeriod $record): string {
                                $shouldPay = $record->taxShouldBePaid();
                                $paid = (float) $record->monthly_tax_paid * 12;
                                $diff = $shouldPay - $paid;

                                $formatted = number_format(abs($diff), 2, ',', '.').' €';

                                if ($diff < 0) {
                                    return "-{$formatted} (you will get returned next year)";
                                } elseif ($diff > 0) {
                                    return "+{$formatted} (you will need to pay more)";
                                }

                                return '0,00 €';
                            })
                            ->color(function (AccountingPeriod $record): string {
                                $shouldPay = $record->taxShouldBePaid();
                                $paid = (float) $record->monthly_tax_paid * 12;
                                $diff = $shouldPay - $paid;

                                if ($diff < 0) {
                                    return 'success';
                                } elseif ($diff > 0) {
                                    return 'danger';
                                }

                                return 'gray';
                            }),
                    ])
                    ->columns(2),
            ]);
    }
}
