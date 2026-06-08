<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Invoices\Tables\InvoiceTableFilters;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    protected static ?string $relatedResource = InvoiceResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('number')
                    ->numeric(),
                Select::make('status')
                    ->options(InvoiceStatus::class)
                    ->required(),
                DatePicker::make('issue_date'),
                DatePicker::make('payment_deadline'),
                DatePicker::make('paid_at'),
                TextInput::make('service_text'),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('number')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('issue_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('payment_deadline')
                    ->date(),
                TextEntry::make('paid_at')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('service_text')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Invoice $record): bool => $record->trashed()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('number')
            ->columns([
                TextColumn::make('number')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('accountingPeriod.year')
                    ->label('Accounting Period')
                    ->sortable(),
                TextColumn::make('issue_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('paid_at')
                    ->date()
                    ->sortable(),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('EUR')
                    ->state(fn (Invoice $record): float => $record->total())
                    ->sortable(false)
                    ->summarize(
                        Summarizer::make('sum')
                            ->label('Total')
                            ->using(fn (QueryBuilder $query): string => Number::currency(
                                (float) InvoiceItem::query()
                                    ->whereIn('invoice_id', (clone $query)->select('invoices.id'))
                                    ->sum(DB::raw('price * quantity')),
                                'EUR',
                            ))
                    ),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->groups([
                Group::make('accountingPeriod.year')
                    ->label('Accounting Period')
                    ->collapsible(),
            ])
            ->filters([
                InvoiceTableFilters::accountingPeriod(),
                InvoiceTableFilters::status(),
                TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make(),
                AssociateAction::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DissociateAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['accountingPeriod', 'items'])
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ]));
    }
}
