<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Models\AccountingPeriod;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Invoice Details')
                    ->schema([
                        Select::make('customer_id')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('accounting_period_id')
                            ->label('Accounting Period')
                            ->relationship('accountingPeriod', 'year')
                            ->searchable()
                            ->preload()
                            ->default(fn () => AccountingPeriod::where('year', now()->year)->first()?->id)
                            ->required(),
                        DatePicker::make('payment_deadline')
                            ->label('Payment Deadline')
                            ->native(false),
                        TextInput::make('service_text')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('payout_amount')
                            ->label('Payout amount')
                            ->helperText('Portion of this invoice paid forward to a third party. Excluded from revenue; not shown on the PDF.')
                            ->numeric()
                            ->prefix('€')
                            ->minValue(0)
                            ->default(0)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Invoice Items')
                    ->schema([
                        Repeater::make('items')
                            ->hiddenLabel()
                            ->relationship()
                            ->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(2),
                                Textarea::make('description')
                                    ->maxLength(500)
                                    ->columnSpan(2),
                                TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required(),
                                TextInput::make('price')
                                    ->numeric()
                                    ->prefix('€')
                                    ->minValue(0)
                                    ->required(),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->reorderable()
                            ->orderColumn('sort')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                            ->addActionLabel('Add Item'),
                    ]),
            ]);
    }
}
