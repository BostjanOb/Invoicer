<?php

use App\Ai\Agents\InvoiceExtractor;
use App\Enums\InvoiceStatus;
use App\Models\AccountingPeriod;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function fakeInvoiceFilePayload(array $overrides = []): array
{
    return array_replace_recursive([
        'customer' => [
            'name' => 'Acme d.o.o.',
            'address' => 'Glavna ulica 1',
            'city' => 'Ljubljana',
            'postcode' => '1000',
            'country' => 'Slovenija',
            'vat_number' => 'SI12345678',
        ],
        'invoice' => [
            'number' => 7,
            'issue_date' => '2025-03-01',
            'payment_deadline' => '2025-03-15',
            'paid_at' => null,
            'service_text' => 'Razvoj programske opreme',
        ],
        'items' => [
            ['title' => 'Razvoj', 'description' => 'Backend', 'price' => 1200.50, 'quantity' => 2],
        ],
    ], $overrides);
}

function makeFakeInvoiceFilePdf(): string
{
    $tempDir = sys_get_temp_dir().'/invoicer-file-import-'.uniqid();
    mkdir($tempDir, 0777, true);
    $path = "{$tempDir}/sample.pdf";
    file_put_contents($path, '%PDF-1.4 fake');

    return $path;
}

afterEach(function () {
    foreach (glob(sys_get_temp_dir().'/invoicer-file-import-*') as $dir) {
        array_map('unlink', glob("{$dir}/*"));
        rmdir($dir);
    }
});

it('imports an invoice into an existing open period', function () {
    $period = AccountingPeriod::factory()->create(['year' => 2025, 'is_closed' => false]);
    InvoiceExtractor::fake([fakeInvoiceFilePayload()]);
    $pdfPath = makeFakeInvoiceFilePdf();

    $this->artisan('invoices:import-file')
        ->expectsChoice('Select accounting period', (string) $period->id, [
            (string) $period->id => '2025 (Open)',
            'new' => 'Create new period / Enter a different year...',
        ])
        ->expectsQuestion('Enter the path to the PDF invoice file', $pdfPath)
        ->expectsOutput('Invoice #7 successfully imported for accounting period 2025.')
        ->assertSuccessful();

    $this->assertDatabaseHas(Customer::class, [
        'vat_number' => 'SI12345678',
        'name' => 'Acme d.o.o.',
    ]);
    $this->assertDatabaseHas(Invoice::class, [
        'accounting_period_id' => $period->id,
        'number' => 7,
        'status' => InvoiceStatus::PAID->value,
    ]);
});

it('imports an invoice into an existing closed period with a warning', function () {
    $period = AccountingPeriod::factory()->create(['year' => 2024, 'is_closed' => true]);
    InvoiceExtractor::fake([fakeInvoiceFilePayload()]);
    $pdfPath = makeFakeInvoiceFilePdf();

    $this->artisan('invoices:import-file')
        ->expectsChoice('Select accounting period', (string) $period->id, [
            (string) $period->id => '2024 (Closed)',
            'new' => 'Create new period / Enter a different year...',
        ])
        ->expectsQuestion('Enter the path to the PDF invoice file', $pdfPath)
        ->expectsOutput('Warning: The selected accounting period 2024 is closed. Proceeding anyway...')
        ->expectsOutput('Invoice #7 successfully imported for accounting period 2024.')
        ->assertSuccessful();

    $this->assertDatabaseHas(Invoice::class, [
        'accounting_period_id' => $period->id,
        'number' => 7,
    ]);
});

it('creates a new period and imports the invoice when choosing new period', function () {
    $period = AccountingPeriod::factory()->create(['year' => 2024, 'is_closed' => false]);
    InvoiceExtractor::fake([fakeInvoiceFilePayload()]);
    $pdfPath = makeFakeInvoiceFilePdf();

    $this->artisan('invoices:import-file')
        ->expectsChoice('Select accounting period', 'new', [
            (string) $period->id => '2024 (Open)',
            'new' => 'Create new period / Enter a different year...',
        ])
        ->expectsQuestion('Enter the year for the new accounting period (4 digits)', '2025')
        ->expectsQuestion('Enter the path to the PDF invoice file', $pdfPath)
        ->expectsOutput('Invoice #7 successfully imported for accounting period 2025.')
        ->assertSuccessful();

    $this->assertDatabaseHas(AccountingPeriod::class, [
        'year' => 2025,
        'is_closed' => false,
    ]);
    $this->assertDatabaseHas(Invoice::class, [
        'number' => 7,
    ]);
});

it('asks directly for year when there are no accounting periods', function () {
    InvoiceExtractor::fake([fakeInvoiceFilePayload()]);
    $pdfPath = makeFakeInvoiceFilePdf();

    $this->artisan('invoices:import-file')
        ->expectsQuestion('Enter the year for the new accounting period (4 digits)', '2026')
        ->expectsQuestion('Enter the path to the PDF invoice file', $pdfPath)
        ->expectsOutput('Invoice #7 successfully imported for accounting period 2026.')
        ->assertSuccessful();

    $this->assertDatabaseHas(AccountingPeriod::class, [
        'year' => 2026,
        'is_closed' => false,
    ]);
});

it('overwrites an existing invoice with the same number in the period', function () {
    $period = AccountingPeriod::factory()->create(['year' => 2025]);
    $customer = Customer::factory()->create();
    $existing = Invoice::factory()
        ->for($period, 'accountingPeriod')
        ->for($customer)
        ->create(['number' => 7]);
    $staleItem = InvoiceItem::factory()->for($existing)->create(['title' => 'Stale']);

    InvoiceExtractor::fake([fakeInvoiceFilePayload()]);
    $pdfPath = makeFakeInvoiceFilePdf();

    $this->artisan('invoices:import-file')
        ->expectsChoice('Select accounting period', (string) $period->id, [
            (string) $period->id => '2025 (Open)',
            'new' => 'Create new period / Enter a different year...',
        ])
        ->expectsQuestion('Enter the path to the PDF invoice file', $pdfPath)
        ->expectsOutput('Invoice #7 successfully overwritten for accounting period 2025.')
        ->assertSuccessful();

    expect(Invoice::where('accounting_period_id', $period->id)->where('number', 7)->count())->toBe(1);
    $this->assertDatabaseMissing(InvoiceItem::class, ['id' => $staleItem->id, 'deleted_at' => null]);
    $this->assertDatabaseHas(InvoiceItem::class, ['title' => 'Razvoj']);
});

it('runs dry-run and does not persist invoice', function () {
    $period = AccountingPeriod::factory()->create(['year' => 2025]);
    InvoiceExtractor::fake([fakeInvoiceFilePayload()]);
    $pdfPath = makeFakeInvoiceFilePdf();

    $this->artisan('invoices:import-file', ['--dry-run' => true])
        ->expectsChoice('Select accounting period', (string) $period->id, [
            (string) $period->id => '2025 (Open)',
            'new' => 'Create new period / Enter a different year...',
        ])
        ->expectsQuestion('Enter the path to the PDF invoice file', $pdfPath)
        ->expectsOutput('Dry-run: Parse successful. Database was not updated.')
        ->assertSuccessful();

    $this->assertDatabaseMissing(Invoice::class, ['number' => 7]);
});
