<?php

namespace App\Filament\Widgets;

use App\Models\AccountingPeriod;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\Widget;

class AccountingPeriodSelector extends Widget implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.widgets.accounting-period-selector';

    protected int|string|array $columnSpan = 'full';

    public ?int $selectedPeriodId = null;

    public function mount(): void
    {
        $defaultPeriod = AccountingPeriod::query()
            ->where('is_closed', false)
            ->latest('year')
            ->first();

        $this->selectedPeriodId = session('selected_accounting_period_id', $defaultPeriod?->id);
    }

    public function updatedSelectedPeriodId(): void
    {
        session(['selected_accounting_period_id' => $this->selectedPeriodId]);

        $this->dispatch('accounting-period-changed', periodId: $this->selectedPeriodId);
    }

    public function getAccountingPeriods(): array
    {
        return AccountingPeriod::query()
            ->orderByDesc('year')
            ->pluck('year', 'id')
            ->toArray();
    }
}
