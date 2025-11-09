<?php

use App\Models\Invoice;
use Illuminate\Support\Facades\Route;

use function Spatie\LaravelPdf\Support\pdf;

// 52
Route::get('/test', function () {
    return view('pdf.invoice', [
        'invoice' => Invoice::find(52),
    ]);

    return pdf()
        ->view('pdf.invoice', [
            'invoice' => Invoice::find(52),
        ])
        ->format(\Spatie\LaravelPdf\Enums\Format::A4)
        ->name('invoice-2023-04-10.pdf');
});
