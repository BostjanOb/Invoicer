<?php

namespace App\Taxes;

use App\Models\Invoice;

class FlatRateTaxCalculator implements TaxCalculator
{
    /**
     * Calculate a flat 4% tax on the invoice total.
     */
    public function calculate(Invoice $invoice): float
    {
        $total = $invoice->total();
        if ($total <= 0) {
            return 0.0;
        }

        return $total * 0.04;
    }
}
