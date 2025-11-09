<?php

namespace App\Filament\Resources\AccountingPeriods\Tables;

use App\Enums\InvoiceStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AccountingPeriodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('year')
                    ->sortable(),
                IconColumn::make('is_closed')
                    ->boolean(),
                TextColumn::make('invoices_by_status')
                    ->label('Invoices')
                    ->getStateUsing(function ($record) {
                        $invoices = $record->invoices()->get()->groupBy('status');

                        $draft = $invoices->get(InvoiceStatus::DRAFT->value)?->count() ?? 0;
                        $issued = $invoices->get(InvoiceStatus::ISSUED->value)?->count() ?? 0;
                        $paid = $invoices->get(InvoiceStatus::PAID->value)?->count() ?? 0;

                        return "Draft: {$draft} | Issued: {$issued} | Paid: {$paid}";
                    })
                    ->searchable(false),
                TextColumn::make('paid_invoices_total')
                    ->label('Paid Total')
                    ->money('EUR')
                    ->getStateUsing(function ($record) {
                        return $record->invoices()
                            ->where('status', 'paid')
                            ->with('items')
                            ->get()
                            ->sum(function ($invoice) {
                                return $invoice->items->sum(function ($item) {
                                    return $item->price * $item->quantity;
                                });
                            });
                    }),
            ])
            ->defaultSort('year', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
