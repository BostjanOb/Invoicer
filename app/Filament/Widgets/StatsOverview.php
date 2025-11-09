<?php

namespace App\Filament\Widgets;

use App\Enums\InvoiceStatus;
use App\Models\AccountingPeriod;
use App\Models\Invoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\On;

class StatsOverview extends BaseWidget
{
    public ?int $selectedPeriodId = null;

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

        // Calculate current period stats
        $currentRevenue = $this->calculateRevenue($currentPeriod->id, InvoiceStatus::PAID);
        $currentOutstanding = $this->calculateRevenue($currentPeriod->id, InvoiceStatus::ISSUED);
        $currentDraft = $this->calculateRevenue($currentPeriod->id, InvoiceStatus::DRAFT);

        // Calculate previous period stats for comparison
        $previousRevenue = $previousPeriod
            ? $this->calculateRevenue($previousPeriod->id, InvoiceStatus::PAID)
            : 0;
        $previousOutstanding = $previousPeriod
            ? $this->calculateRevenue($previousPeriod->id, InvoiceStatus::ISSUED)
            : 0;
        $previousDraft = $previousPeriod
            ? $this->calculateRevenue($previousPeriod->id, InvoiceStatus::DRAFT)
            : 0;

        return [
            Stat::make('Total Revenue', $this->formatCurrency($currentRevenue))
                ->description($this->getComparisonText($currentRevenue, $previousRevenue))
                ->descriptionIcon($this->getComparisonIcon($currentRevenue, $previousRevenue))
                ->color($this->getComparisonColor($currentRevenue, $previousRevenue))
                ->chart($this->getMonthlyData($currentPeriod->id, InvoiceStatus::PAID)),

            Stat::make('Outstanding Amount', $this->formatCurrency($currentOutstanding))
                ->description($this->getComparisonText($currentOutstanding, $previousOutstanding))
                ->descriptionIcon($this->getComparisonIcon($currentOutstanding, $previousOutstanding))
                ->color($this->getComparisonColor($currentOutstanding, $previousOutstanding, true))
                ->chart($this->getMonthlyData($currentPeriod->id, InvoiceStatus::ISSUED)),

            Stat::make('Draft Value', $this->formatCurrency($currentDraft))
                ->description($this->getComparisonText($currentDraft, $previousDraft))
                ->descriptionIcon($this->getComparisonIcon($currentDraft, $previousDraft))
                ->color('gray')
                ->chart($this->getMonthlyData($currentPeriod->id, InvoiceStatus::DRAFT)),
        ];
    }

    protected function calculateRevenue(int $periodId, InvoiceStatus $status): float
    {
        return Invoice::query()
            ->where('accounting_period_id', $periodId)
            ->where('status', $status)
            ->with('items')
            ->get()
            ->sum(fn (Invoice $invoice) => $invoice->total());
    }

    protected function getMonthlyData(int $periodId, InvoiceStatus $status): array
    {
        $period = AccountingPeriod::find($periodId);

        if (! $period) {
            return [];
        }

        $monthlyTotals = [];

        for ($month = 1; $month <= 12; $month++) {
            $total = Invoice::query()
                ->where('accounting_period_id', $periodId)
                ->where('status', $status)
                ->whereYear('created_at', $period->year)
                ->whereMonth('created_at', $month)
                ->with('items')
                ->get()
                ->sum(fn (Invoice $invoice) => $invoice->total());

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

    protected function getComparisonColor(float $current, float $previous, bool $inverse = false): string
    {
        if ($previous == 0 || abs((($current - $previous) / $previous) * 100) < 0.01) {
            return 'gray';
        }

        $isIncrease = $current > $previous;

        if ($inverse) {
            return $isIncrease ? 'danger' : 'success';
        }

        return $isIncrease ? 'success' : 'danger';
    }
}
