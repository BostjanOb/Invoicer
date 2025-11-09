<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

class EditProfile extends BaseEditProfile
{
    protected Width|string|null $maxContentWidth = Width::FiveExtraLarge;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Personal Information')
                    ->schema([
                        $this->getNameFormComponent(),
                        $this->getEmailFormComponent(),
                        TextInput::make('phone')
                            ->label('Phone')
                            ->tel()
                            ->maxLength(255),
                        FileUpload::make('signature')
                            ->label('Signature')
                            ->image()
                            ->maxSize(2048)
                            ->directory('signatures')
                            ->visibility('private'),
                    ])
                    ->columns(),

                Section::make('Password')
                    ->schema([
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ])
                    ->columns(),

                Section::make('Company Information')
                    ->schema([
                        TextInput::make('company_name')
                            ->label('Company Name')
                            ->maxLength(255),
                        TextInput::make('company_vat_number')
                            ->label('VAT Number')
                            ->maxLength(255),
                        FileUpload::make('company_logo')
                            ->label('Company Logo')
                            ->image()
                            ->maxSize(2048)
                            ->directory('company-logos')
                            ->visibility('private'),
                    ])
                    ->columns(),

                Section::make('Company Address')
                    ->schema([
                        TextInput::make('company_address')
                            ->label('Address')
                            ->maxLength(255),
                        TextInput::make('company_city')
                            ->label('City')
                            ->maxLength(255),
                        TextInput::make('company_postcode')
                            ->label('Postcode')
                            ->maxLength(255),
                        TextInput::make('company_country')
                            ->label('Country')
                            ->maxLength(255),
                    ])
                    ->columns(),

                Section::make('Bank Information')
                    ->schema([
                        TextInput::make('bank_name')
                            ->label('Bank Name')
                            ->maxLength(255),
                        TextInput::make('bank_iban')
                            ->label('IBAN')
                            ->maxLength(255),
                        TextInput::make('bank_bic')
                            ->label('BIC/SWIFT')
                            ->maxLength(255),
                    ])
                    ->columns(),
            ]);
    }
}
