<?php

namespace App\Filament\Widgets;

use App\Enums\InvoiceStatus;
use App\Models\AccountingPeriod;
use App\Models\Invoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;

class StatsOverview extends BaseWidget
{
    public ?int $selectedPeriodId = null;

    protected static ?int $sort = 2;

    #[On('accounting-period-changed')]
    public function updatePeriod(int $periodId): void
    {
        $this->selectedPeriodId = $periodId;
    }

    public function mount(): void
    {
        $defaultPeriod = AccountingPeriod::query()
            ->where('is_closed', false)
            ->latest('year')
            ->first();

        $this->selectedPeriodId = session('selected_accounting_period_id', $defaultPeriod?->id);
    }

    protected function getStats(): array
    {
        $currentPeriod = AccountingPeriod::find($this->selectedPeriodId);

        if (! $currentPeriod) {
            return [];
        }

        $previousPeriod = AccountingPeriod::query()
            ->where('year', $currentPeriod->year - 1)
            ->first();

        // Revenue counts both issued and paid invoices (drafts excluded).
        $revenueStatuses = [InvoiceStatus::PAID, InvoiceStatus::ISSUED];

        // Current displayed value is the full period total to date.
        $currentRevenue = $this->calculateRevenue($currentPeriod->id, $revenueStatuses, deductPayout: true);

        // Comparison baseline is capped to the same point in the previous year
        // so an in-progress year is not measured against a full prior year.
        $previousRevenue = $previousPeriod
            ? $this->calculateRevenue(
                $previousPeriod->id,
                $revenueStatuses,
                $this->yearToDateCutoff($previousPeriod->year),
                deductPayout: true,
            )
            : 0;

        $currentOutstanding = $this->calculateRevenue($currentPeriod->id, InvoiceStatus::ISSUED);
        $currentDraft = $this->calculateRevenue($currentPeriod->id, InvoiceStatus::DRAFT);

        return [
            Stat::make('Total Revenue', $this->formatCurrency($currentRevenue))
                ->description($this->getComparisonText($currentRevenue, $previousRevenue))
                ->descriptionIcon($this->getComparisonIcon($currentRevenue, $previousRevenue))
                ->color($this->getComparisonColor($currentRevenue, $previousRevenue))
                ->chart($this->getMonthlyData($currentPeriod->id, $revenueStatuses, deductPayout: true)),

            Stat::make('Outstanding Amount', $this->formatCurrency($currentOutstanding))
                ->color('warning')
                ->chart($this->getMonthlyData($currentPeriod->id, InvoiceStatus::ISSUED)),

            Stat::make('Draft Value', $this->formatCurrency($currentDraft))
                ->color('gray')
                // Drafts have no issue_date yet, so chart them by creation date.
                ->chart($this->getMonthlyData($currentPeriod->id, InvoiceStatus::DRAFT, 'created_at')),
        ];
    }

    /**
     * @param  array<int, InvoiceStatus>|InvoiceStatus  $statuses
     */
    protected function calculateRevenue(int $periodId, array|InvoiceStatus $statuses, ?Carbon $issuedOnOrBefore = null, bool $deductPayout = false): float
    {
        $values = collect(is_array($statuses) ? $statuses : [$statuses])
            ->map(fn (InvoiceStatus $status) => $status->value);

        return Invoice::query()
            ->where('accounting_period_id', $periodId)
            ->whereIn('status', $values)
            ->when($issuedOnOrBefore, fn ($query) => $query->where('issue_date', '<=', $issuedOnOrBefore))
            ->with('items')
            ->get()
            ->sum(fn (Invoice $invoice) => $deductPayout ? $invoice->netRevenue() : $invoice->total());
    }

    /**
     * Maps "today" onto the given year so an in-progress year is compared
     * against the previous year up to the same point in the year (by issue date).
     */
    protected function yearToDateCutoff(int $year): Carbon
    {
        return Carbon::create($year, 1, 1)
            ->addDays(now()->dayOfYear - 1)
            ->endOfDay();
    }

    /**
     * @param  array<int, InvoiceStatus>|InvoiceStatus  $statuses
     * @return array<int, float>
     */
    protected function getMonthlyData(int $periodId, array|InvoiceStatus $statuses, string $dateColumn = 'issue_date', bool $deductPayout = false): array
    {
        $period = AccountingPeriod::find($periodId);

        if (! $period) {
            return [];
        }

        $values = collect(is_array($statuses) ? $statuses : [$statuses])
            ->map(fn (InvoiceStatus $status) => $status->value);

        $monthlyTotals = [];

        for ($month = 1; $month <= 12; $month++) {
            $total = Invoice::query()
                ->where('accounting_period_id', $periodId)
                ->whereIn('status', $values)
                ->whereYear($dateColumn, $period->year)
                ->whereMonth($dateColumn, $month)
                ->with('items')
                ->get()
                ->sum(fn (Invoice $invoice) => $deductPayout ? $invoice->netRevenue() : $invoice->total());

            $monthlyTotals[] = $total;
        }

        return $monthlyTotals;
    }

    protected function formatCurrency(float $amount): string
    {
        return '€'.number_format($amount, 2);
    }

    protected function getComparisonText(float $current, float $previous): string
    {
        if ($previous == 0) {
            return $current > 0 ? 'First year' : 'No data';
        }

        $percentageChange = (($current - $previous) / $previous) * 100;

        return abs($percentageChange) < 0.01
            ? 'No change from last year'
            : sprintf('%s%.1f%% from last year', $percentageChange > 0 ? '+' : '', $percentageChange);
    }

    protected function getComparisonIcon(float $current, float $previous): string
    {
        if ($previous == 0) {
            return 'heroicon-m-minus';
        }

        $percentageChange = (($current - $previous) / $previous) * 100;

        if (abs($percentageChange) < 0.01) {
            return 'heroicon-m-minus';
        }

        return $percentageChange > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
    }

    protected function getComparisonColor(float $current, float $previous): string
    {
        if ($previous == 0 || abs((($current - $previous) / $previous) * 100) < 0.01) {
            return 'gray';
        }

        return $current > $previous ? 'success' : 'danger';
    }
}
