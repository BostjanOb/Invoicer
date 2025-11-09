<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\DB;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('issue')
                ->label('Issue Invoice')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (Invoice $record): bool => $record->status === InvoiceStatus::DRAFT)
                ->action(function (Invoice $record): void {
                    // Get the next sequential number for this accounting period
                    $sequentialNumber = Invoice::where('status', '!=', InvoiceStatus::DRAFT)
                        ->where('accounting_period_id', $record->accounting_period_id)
                        ->max('number') + 1;

                    $invoiceNumber = sprintf('%03d-%d', $sequentialNumber, $record->accountingPeriod->year);

                    $record->update([
                        'status' => InvoiceStatus::ISSUED,
                        'number' => $sequentialNumber,
                        'issue_date' => now(),
                    ]);

                    Notification::make()
                        ->title('Invoice issued successfully')
                        ->body("Invoice number: {$invoiceNumber}")
                        ->success()
                        ->send();
                }),
            Action::make('markAsPaid')
                ->label('Mark as Paid')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (Invoice $record): bool => $record->status === InvoiceStatus::ISSUED)
                ->modalWidth(Width::Small)
                ->schema([
                    \Filament\Forms\Components\DatePicker::make('paid_at')
                        ->label('Payment Date')
                        ->default(now())
                        ->native(false)
                        ->required(),
                ])
                ->action(function (Invoice $record, array $data): void {
                    $record->update([
                        'status' => InvoiceStatus::PAID,
                        'paid_at' => $data['paid_at'],
                    ]);

                    Notification::make()
                        ->title('Invoice marked as paid')
                        ->success()
                        ->send();
                }),
            EditAction::make(),
            Action::make('duplicate')
                ->label('Duplicate')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Duplicate Invoice')
                ->modalDescription('This will create a new draft invoice with the same details and items.')
                ->action(function (Invoice $record): void {
                    $newInvoice = DB::transaction(function () use ($record) {
                        // Create the duplicate invoice
                        $duplicate = $record->replicate([
                            'number',
                            'issue_date',
                            'paid_at',
                        ]);
                        $duplicate->status = InvoiceStatus::DRAFT;
                        $duplicate->accounting_period_id = \App\Models\AccountingPeriod::where('year', now()->year)->first()?->id;
                        $duplicate->save();

                        // Duplicate all invoice items
                        foreach ($record->items as $item) {
                            $duplicateItem = $item->replicate();
                            $duplicateItem->invoice_id = $duplicate->id;
                            $duplicateItem->save();
                        }

                        return $duplicate;
                    });

                    Notification::make()
                        ->title('Invoice duplicated successfully')
                        ->success()
                        ->send();

                    $this->redirect(InvoiceResource::getUrl('edit', ['record' => $newInvoice]));
                }),
        ];
    }
}
