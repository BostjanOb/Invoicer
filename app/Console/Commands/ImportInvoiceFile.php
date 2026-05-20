<?php

namespace App\Console\Commands;

use App\Ai\Agents\InvoiceExtractor;
use App\Enums\InvoiceStatus;
use App\Models\AccountingPeriod;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Files\Document;
use Throwable;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[Signature('invoices:import-file {--dry-run : Parse PDF and report without writing to the database}')]
#[Description('Import a single PDF invoice file by prompting for accounting period and file path.')]
class ImportInvoiceFile extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $periods = AccountingPeriod::orderBy('year', 'desc')->get();

        if ($periods->isEmpty()) {
            $year = text(
                label: 'Enter the year for the new accounting period (4 digits)',
                placeholder: 'e.g. 2026',
                required: true,
                validate: fn (string $value) => ! preg_match('/^\d{4}$/', $value)
                    ? 'The year must be a 4-digit number.'
                    : null
            );

            $period = AccountingPeriod::create([
                'year' => (int) $year,
                'is_closed' => false,
            ]);
        } else {
            $options = [];
            foreach ($periods as $p) {
                $options[(string) $p->id] = "{$p->year} (".($p->is_closed ? 'Closed' : 'Open').')';
            }
            $options['new'] = 'Create new period / Enter a different year...';

            $selected = select(
                label: 'Select accounting period',
                options: $options,
                default: (string) $periods->first()->id
            );

            if ($selected === 'new') {
                $year = text(
                    label: 'Enter the year for the new accounting period (4 digits)',
                    placeholder: 'e.g. 2026',
                    required: true,
                    validate: fn (string $value) => match (true) {
                        ! preg_match('/^\d{4}$/', $value) => 'The year must be a 4-digit number.',
                        AccountingPeriod::where('year', (int) $value)->exists() => 'An accounting period for this year already exists.',
                        default => null,
                    }
                );

                $period = AccountingPeriod::create([
                    'year' => (int) $year,
                    'is_closed' => false,
                ]);
            } else {
                $period = AccountingPeriod::find((int) $selected);
            }
        }

        $filePath = text(
            label: 'Enter the path to the PDF invoice file',
            placeholder: 'e.g., /path/to/invoice.pdf',
            required: true,
            validate: fn (string $value) => match (true) {
                ! file_exists($value) => 'The file does not exist.',
                ! is_file($value) => 'The path must point to a file, not a directory.',
                strtolower(pathinfo($value, PATHINFO_EXTENSION)) !== 'pdf' => 'The file must be a PDF.',
                default => null,
            }
        );

        try {
            $data = (new InvoiceExtractor)->prompt(
                'Extract the invoice from the attached PDF.',
                attachments: [Document::fromPath($filePath)],
            )->toArray();

            if ($dryRun) {
                $this->reportDryRun(basename($filePath), $data);
                $this->info('Dry-run: Parse successful. Database was not updated.');

                return self::SUCCESS;
            }

            if ($period->is_closed) {
                $this->warn("Warning: The selected accounting period {$period->year} is closed. Proceeding anyway...");
            }

            $wasOverwrite = $this->importInvoice($period, $data);

            if ($wasOverwrite) {
                $this->info("Invoice #{$data['invoice']['number']} successfully overwritten for accounting period {$period->year}.");
            } else {
                $this->info("Invoice #{$data['invoice']['number']} successfully imported for accounting period {$period->year}.");
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error(basename($filePath).': '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Persist a single extracted invoice. Returns true if an existing invoice was overwritten.
     *
     * @param  array{customer: array<string, string>, invoice: array<string, mixed>, items: array<int, array<string, mixed>>}  $data
     */
    private function importInvoice(AccountingPeriod $period, array $data): bool
    {
        return DB::transaction(function () use ($period, $data): bool {
            $customer = Customer::firstOrCreate(
                ['vat_number' => $data['customer']['vat_number']],
                [
                    'name' => $data['customer']['name'],
                    'address' => $data['customer']['address'],
                    'city' => $data['customer']['city'],
                    'postcode' => $data['customer']['postcode'],
                    'country' => $data['customer']['country'],
                ],
            );

            $number = (int) $data['invoice']['number'];

            $existing = Invoice::withTrashed()
                ->where('accounting_period_id', $period->id)
                ->where('number', $number)
                ->first();

            $wasOverwrite = false;

            if ($existing) {
                $existing->items()->forceDelete();
                $existing->forceDelete();
                $wasOverwrite = true;
            }

            $invoice = Invoice::create([
                'customer_id' => $customer->id,
                'accounting_period_id' => $period->id,
                'number' => $number,
                'status' => InvoiceStatus::PAID,
                'issue_date' => $data['invoice']['issue_date'],
                'payment_deadline' => $data['invoice']['payment_deadline'],
                'paid_at' => $data['invoice']['paid_at'] ?: $data['invoice']['payment_deadline'],
                'service_text' => $data['invoice']['service_text'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $invoice->items()->create([
                    'title' => $item['title'],
                    'description' => $item['description'] ?? null,
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                ]);
            }

            return $wasOverwrite;
        });
    }

    /**
     * @param  array{customer: array<string, string>, invoice: array<string, mixed>, items: array<int, array<string, mixed>>}  $data
     */
    private function reportDryRun(string $file, array $data): void
    {
        $this->newLine();
        $this->line("<info>{$file}</info> — #{$data['invoice']['number']} · {$data['customer']['name']} ({$data['customer']['vat_number']})");
        $this->table(
            ['Title', 'Price', 'Qty'],
            collect($data['items'])->map(fn (array $i): array => [
                $i['title'],
                $i['price'],
                $i['quantity'],
            ])->all(),
        );
    }
}
