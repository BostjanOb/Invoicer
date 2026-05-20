<?php

namespace App\Filament\Resources\AccountingPeriods\Schemas;

use App\Models\AccountingPeriod;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AccountingPeriodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('year')
                    ->required()
                    ->numeric(),
                Toggle::make('is_closed')
                    ->inline(false)
                    ->required(),
                TextInput::make('monthly_tax_paid')
                    ->label('Monthly Tax Paid')
                    ->numeric()
                    ->prefix('€')
                    ->minValue(0)
                    ->default(0.00)
                    ->required(),
                Select::make('tax_calculator')
                    ->label('Tax Calculator')
                    ->options(AccountingPeriod::getAvailableCalculators())
                    ->placeholder('Select tax calculator')
                    ->required(),
            ]);
    }
}
