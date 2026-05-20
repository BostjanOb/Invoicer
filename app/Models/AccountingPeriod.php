<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Taxes\FlatRateTaxCalculator;
use App\Taxes\ProgressiveTaxCalculator;
use App\Taxes\TaxCalculator;
use Database\Factories\AccountingPeriodFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingPeriod extends Model
{
    /** @use HasFactory<AccountingPeriodFactory> */
    use HasFactory;

    protected $fillable = [
        'year',
        'is_closed',
        'monthly_tax_paid',
        'tax_calculator',
    ];

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function getTaxCalculator(): TaxCalculator
    {
        $class = $this->tax_calculator;

        if ($class && class_exists($class)) {
            return new $class;
        }

        // Fallback by year if not explicitly configured
        if (in_array($this->year, [2025, 2026])) {
            return new ProgressiveTaxCalculator;
        }

        return new FlatRateTaxCalculator;
    }

    public function taxShouldBePaid(): float
    {
        $calculator = $this->getTaxCalculator();

        return (float) $this->invoices()
            ->whereIn('status', [InvoiceStatus::ISSUED, InvoiceStatus::PAID])
            ->get()
            ->sum(fn (Invoice $invoice) => $calculator->calculate($invoice));
    }

    /**
     * @return array<string, string>
     */
    public static function getAvailableCalculators(): array
    {
        return [
            FlatRateTaxCalculator::class => 'Flat Rate (4%)',
            ProgressiveTaxCalculator::class => 'Progressive (4% / 12% / 30%)',
        ];
    }

    protected function casts(): array
    {
        return [
            'is_closed' => 'boolean',
            'monthly_tax_paid' => 'decimal:2',
        ];
    }
}
