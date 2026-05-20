<?php

namespace App\Taxes;

use App\Models\Invoice;

interface TaxCalculator
{
    /**
     * Calculate the tax for the given invoice.
     */
    public function calculate(Invoice $invoice): float;
}
