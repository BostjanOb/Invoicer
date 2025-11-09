<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Customer Information')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('vat_number')
                            ->label('VAT Number'),
                    ])
                    ->columns(),

                Section::make('Address')
                    ->schema([
                        TextEntry::make('address'),
                        TextEntry::make('city'),
                        TextEntry::make('postcode'),
                        TextEntry::make('country'),
                    ])
                    ->columns(),
            ]);
    }
}
