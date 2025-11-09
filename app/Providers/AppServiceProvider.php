<?php

namespace App\Providers;

use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Support\Number;
use Illuminate\Support\ServiceProvider;
use Spatie\LaravelPdf\Enums\Format;
use Spatie\LaravelPdf\Facades\Pdf;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Number::useLocale('sl');
        Number::useCurrency('eur');

        Pdf::default()
            ->margins(15, 15, 15, 15)
            ->format(Format::A4);

        Table::configureUsing(function (Table $table) {
            $table->defaultCurrency('eur');
        });

        Schema::configureUsing(function (Schema $schema) {
            $schema->defaultCurrency('eur');
            $schema->defaultDateDisplayFormat('j. n. Y');
        });
    }
}
