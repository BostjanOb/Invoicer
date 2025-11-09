<?php

namespace App\Filament\Widgets;

use App\Enums\InvoiceStatus;
use App\Models\AccountingPeriod;
use App\Models\Invoice;
use Filament\Widgets\ChartWidget;
use Livewire\Attributes\On;

class RevenueTrends extends ChartWidget
{
    protected ?string $heading = 'Revenue Trends';

    protected ?string $maxHeight = '300px';

    public ?int $selectedPeriodId = null;

    protected static ?int $sort = 4;

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

    protected function getData(): array
    {
        $currentPeriod = AccountingPeriod::find($this->selectedPeriodId);

        if (! $currentPeriod) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $previousPeriod = AccountingPeriod::query()
            ->where('year', $currentPeriod->year - 1)
            ->first();

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        $currentYearPaid = $this->getMonthlyRevenue($currentPeriod->id, InvoiceStatus::PAID);
        $currentYearIssued = $this->getMonthlyRevenue($currentPeriod->id, InvoiceStatus::ISSUED);

        $datasets = [
            [
                'label' => $currentPeriod->year.' - Paid',
                'data' => $currentYearPaid,
                'borderColor' => 'rgb(34, 197, 94)',
                'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                'fill' => true,
            ],
            [
                'label' => $currentPeriod->year.' - Issued',
                'data' => $currentYearIssued,
                'borderColor' => 'rgb(251, 146, 60)',
                'backgroundColor' => 'rgba(251, 146, 60, 0.1)',
                'fill' => true,
            ],
        ];

        if ($previousPeriod) {
            $previousYearPaid = $this->getMonthlyRevenue($previousPeriod->id, InvoiceStatus::PAID);

            $datasets[] = [
                'label' => $previousPeriod->year.' - Paid',
                'data' => $previousYearPaid,
                'borderColor' => 'rgb(156, 163, 175)',
                'backgroundColor' => 'rgba(156, 163, 175, 0.1)',
                'borderDash' => [5, 5],
                'fill' => false,
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getMonthlyRevenue(int $periodId, InvoiceStatus $status): array
    {
        $period = AccountingPeriod::find($periodId);

        if (! $period) {
            return array_fill(0, 12, 0);
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

            $monthlyTotals[] = round($total, 2);
        }

        return $monthlyTotals;
    }
}
