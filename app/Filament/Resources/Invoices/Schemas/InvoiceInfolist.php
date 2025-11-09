<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InvoiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(5)
            ->components([
                Section::make('Invoice Details')
                    ->columnSpan(3)
                    ->schema([
                        TextEntry::make('number')
                            ->badge()
                            ->placeholder('Not yet issued')
                            ->color(fn (?string $state): string => $state ? 'primary' : 'gray'),
                        TextEntry::make('accounting_period')
                            ->label('Accounting Period')
                            ->placeholder('-'),
                        TextEntry::make('customer.name')
                            ->label('Customer'),
                        TextEntry::make('service_text')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(),

                Section::make('Metadata')
                    ->columnSpan(2)
                    ->inlineLabel()
                    ->schema([
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('issue_date')
                            ->date()
                            ->placeholder('Not yet issued'),
                        TextEntry::make('payment_deadline')
                            ->label('Due Date')
                            ->date(),
                        TextEntry::make('paid_at')
                            ->label('Paid Date')
                            ->date()
                            ->placeholder('-')
                            ->visible(fn (Invoice $record): bool => $record->status === InvoiceStatus::PAID),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                        TextEntry::make('deleted_at')
                            ->dateTime()
                            ->visible(fn (Invoice $record): bool => $record->trashed()),
                    ])
                    ->collapsible(),

                Section::make('Invoice Items')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make('Title'),
                                TableColumn::make('Description'),
                                TableColumn::make('Price'),
                                TableColumn::make('Quantity'),
                                TableColumn::make('Sum'),
                            ])
                            ->schema([
                                TextEntry::make('title'),
                                TextEntry::make('description')
                                    ->limit(50),
                                TextEntry::make('price')
                                    ->money(),
                                TextEntry::make('quantity')
                                    ->numeric(),
                                TextEntry::make('sum')
                                    ->state(fn (InvoiceItem $record): float => $record->price * $record->quantity)
                                    ->money(),
                            ])
                            ->columns(4)
                            ->contained(false),
                        TextEntry::make('total')
                            ->label('Total Amount')
                            ->money('EUR')
                            ->inlineLabel()
                            ->columnSpanFull()
                            ->alignRight()
                            ->size('lg')
                            ->weight('bold')
                            ->state(fn (Invoice $record): float => $record->items->sum(fn ($item) => $item->price * $item->quantity)),
                    ]),

            ]);
    }
}
