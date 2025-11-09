<?php

namespace App\Filament\Resources\AccountingPeriods\Pages;

use App\Filament\Resources\AccountingPeriods\AccountingPeriodResource;
use App\Filament\Resources\AccountingPeriods\RelationManagers\InvoicesRelationManager;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAccountingPeriod extends ViewRecord
{
    protected static string $resource = AccountingPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    protected function getAllRelationManagers(): array
    {
        return [
            InvoicesRelationManager::class,
        ];
    }
}
