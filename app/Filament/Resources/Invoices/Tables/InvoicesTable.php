<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Enums\InvoiceStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (?string $state): string => sprintf('%03d', $state))
                    ->placeholder('Draft')
                    ->badge()
                    ->color(fn (?string $state): string => $state ? 'primary' : 'gray'),
                TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('issue_date')
                    ->date()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('payment_deadline')
                    ->label('Due Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('EUR')
                    ->state(fn ($record): float => $record->items->sum(fn ($item) => $item->price * $item->quantity))
                    ->sortable(false),
                TextColumn::make('service_text')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('issue_date', 'desc')
            ->filters([
                SelectFilter::make('customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options(InvoiceStatus::class),
                SelectFilter::make('accounting_period_id')
                    ->label('Accounting Period')
                    ->relationship('accountingPeriod', 'year')
                    ->default(function () {
                        return \App\Models\AccountingPeriod::where('is_closed', false)
                            ->orderBy('year', 'desc')
                            ->first()?->id;
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
