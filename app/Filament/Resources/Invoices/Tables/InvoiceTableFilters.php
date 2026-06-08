<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Enums\InvoiceStatus;
use App\Models\AccountingPeriod;
use Filament\Tables\Filters\SelectFilter;

class InvoiceTableFilters
{
    /**
     * @return array<int, SelectFilter>
     */
    public static function all(): array
    {
        return [
            self::customer(),
            self::status(),
            self::accountingPeriod()
                ->default(fn (): ?int => AccountingPeriod::query()
                    ->where('is_closed', false)
                    ->latest('year')
                    ->first()?->id),
        ];
    }

    public static function customer(): SelectFilter
    {
        return SelectFilter::make('customer')
            ->relationship('customer', 'name')
            ->searchable()
            ->preload();
    }

    public static function status(?array $options = null): SelectFilter
    {
        return SelectFilter::make('status')
            ->options($options ?? InvoiceStatus::class);
    }

    public static function accountingPeriod(): SelectFilter
    {
        return SelectFilter::make('accounting_period_id')
            ->label('Year Period')
            ->relationship('accountingPeriod', 'year');
    }
}
