<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Models\Invoice;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
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
                    ->weight(FontWeight::SemiBold)
                    ->state(fn (Invoice $record): float => $record->total())
                    ->sortable(false),
                TextColumn::make('payout_amount')
                    ->label('Payout')
                    ->money('EUR')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('service_text')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('issue_date', 'desc')
            ->filters([
                ...InvoiceTableFilters::all(),
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
