<?php

use App\Models\Invoice;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPdf\Facades\Pdf;

Route::middleware('auth')->group(function () {
    Route::get('/invoices/{invoice}/pdf', function (Invoice $invoice) {
        $invoice->load(['customer', 'items', 'accountingPeriod']);
        $user = auth()->user();

        return Pdf::view('pdf.invoice', compact('invoice', 'user'))
            ->inline("invoice-{$invoice->fullNumber()}.pdf");
    })->name('invoice.pdf');
});
