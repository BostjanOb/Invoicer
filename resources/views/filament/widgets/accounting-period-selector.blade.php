<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center gap-4">
            <span class="text-sm font-medium">Accounting Period:</span>
            <select
                wire:model.live="selectedPeriodId"
                class="border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500"
            >
                @foreach($this->getAccountingPeriods() as $id => $year)
                    <option value="{{ $id }}">{{ $year }}</option>
                @endforeach
            </select>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
