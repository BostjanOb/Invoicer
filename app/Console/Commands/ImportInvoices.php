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

#[Signature('invoices:import {path : Folder containing 4-digit year subfolders of invoice PDFs} {--dry-run : Parse PDFs and report without writing to the database}')]
#[Description('Import invoice PDFs (grouped into year subfolders) using AI extraction.')]
class ImportInvoices extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $path = rtrim((string) $this->argument('path'), '/');
        $dryRun = (bool) $this->option('dry-run');

        if (! is_dir($path)) {
            $this->error("Path is not a directory: {$path}");

            return self::FAILURE;
        }

        $imported = 0;
        $overwritten = 0;
        $skipped = 0;
        $failed = 0;

        foreach (glob($path.'/*', GLOB_ONLYDIR) as $yearDir) {
            $year = basename($yearDir);

            if (! preg_match('/^\d{4}$/', $year)) {
                $this->warn("Skipping non-year folder: {$year}");

                continue;
            }

            $period = $dryRun
                ? AccountingPeriod::firstOrNew(['year' => (int) $year], ['is_closed' => false])
                : AccountingPeriod::firstOrCreate(['year' => (int) $year], ['is_closed' => false]);

            if ($period->exists && $period->is_closed) {
                $this->warn("Skipping closed accounting period: {$year}");

                continue;
            }

            $pdfs = collect(glob($yearDir.'/*.{pdf,PDF}', GLOB_BRACE));

            if ($pdfs->isEmpty()) {
                $this->warn("No PDFs found in: {$year}");

                continue;
            }

            $this->info("Processing {$pdfs->count()} invoice(s) for {$year}...");
            $bar = $this->output->createProgressBar($pdfs->count());
            $bar->start();

            foreach ($pdfs as $pdf) {
                try {
                    $data = (new InvoiceExtractor)->prompt(
                        'Extract the invoice from the attached PDF.',
                        attachments: [Document::fromPath($pdf)],
                    )->toArray();

                    if ($dryRun) {
                        $bar->clear();
                        $this->reportDryRun(basename($pdf), $data);
                        $bar->display();
                        $skipped++;

                        continue;
                    }

                    $wasOverwrite = $this->importInvoice($period, $data);
                    $wasOverwrite ? $overwritten++ : $imported++;
                } catch (Throwable $e) {
                    $failed++;
                    $bar->clear();
                    $this->error(basename($pdf).': '.$e->getMessage());
                    $bar->display();
                } finally {
                    $bar->advance();
                }
            }

            $bar->finish();
            $this->newLine(2);
        }

        $this->table(
            ['Imported', 'Overwritten', 'Dry-run', 'Failed'],
            [[$imported, $overwritten, $skipped, $failed]],
        );

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
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
