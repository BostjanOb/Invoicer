<?php

namespace App\Filament\Resources\AccountingPeriods\Schemas;

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
            ]);
    }
}
