<?php

namespace App\Taxes;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;

class ProgressiveTaxCalculator implements TaxCalculator
{
    /**
     * Calculate tax progressively based on cumulative invoice totals in the accounting period.
     *
     * Brackets:
     * - Under 12,500 is taxed at 4%
     * - Between 12,500 and 30,000 is taxed at 12%
     * - Above 30,000 is taxed at 30%
     */
    public function calculate(Invoice $invoice): float
    {
        $invoiceTotal = $invoice->total();
        if ($invoiceTotal <= 0) {
            return 0.0;
        }

        $period = $invoice->accountingPeriod;

        // Query other issued/paid invoices in the same accounting period
        $query = $period->invoices()
            ->whereIn('status', [InvoiceStatus::ISSUED, InvoiceStatus::PAID]);

        // If the invoice is already issued/paid, only sum invoices with a lower sequential number
        if ($invoice->status !== InvoiceStatus::DRAFT && $invoice->number !== null) {
            $query->where('number', '<', $invoice->number);
        }

        $start = (float) $query->get()->sum(fn (Invoice $inv) => $inv->total());
        $end = $start + $invoiceTotal;

        // Brackets definition
        $brackets = [
            ['start' => 0.0, 'end' => 12500.0, 'rate' => 0.04],
            ['start' => 12500.0, 'end' => 30000.0, 'rate' => 0.12],
            ['start' => 30000.0, 'end' => INF, 'rate' => 0.30],
        ];

        $tax = 0.0;
        foreach ($brackets as $bracket) {
            $bStart = $bracket['start'];
            $bEnd = $bracket['end'];
            $rate = $bracket['rate'];

            $overlapStart = max($start, $bStart);
            $overlapEnd = min($end, $bEnd);

            if ($overlapStart < $overlapEnd) {
                $tax += ($overlapEnd - $overlapStart) * $rate;
            }
        }

        return $tax;
    }
}
