<?php

use App\Ai\Agents\InvoiceExtractor;
use App\Enums\InvoiceStatus;
use App\Models\AccountingPeriod;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function fakeInvoicePayload(array $overrides = []): array
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

function makeImportDir(array $folders): string
{
    $base = sys_get_temp_dir().'/invoicer-import-'.uniqid();

    foreach ($folders as $folder => $files) {
        $dir = "{$base}/{$folder}";
        mkdir($dir, 0777, true);

        foreach ($files as $file) {
            file_put_contents("{$dir}/{$file}", '%PDF-1.4 fake');
        }
    }

    return $base;
}

afterEach(function () {
    foreach (glob(sys_get_temp_dir().'/invoicer-import-*') as $dir) {
        array_map('unlink', glob("{$dir}/*/*"));
        array_map('rmdir', glob("{$dir}/*"));
        rmdir($dir);
    }
});

it('imports an invoice from a year subfolder', function () {
    InvoiceExtractor::fake([fakeInvoicePayload()]);
    $path = makeImportDir(['2025' => ['sample.pdf']]);

    $this->artisan('invoices:import', ['path' => $path])->assertSuccessful();

    $this->assertDatabaseHas(AccountingPeriod::class, ['year' => 2025]);
    $this->assertDatabaseHas(Customer::class, [
        'vat_number' => 'SI12345678',
        'name' => 'Acme d.o.o.',
    ]);
    $this->assertDatabaseHas(Invoice::class, [
        'number' => 7,
        'status' => InvoiceStatus::PAID->value,
        'issue_date' => '2025-03-01 00:00:00',
        'payment_deadline' => '2025-03-15 00:00:00',
        'paid_at' => '2025-03-15 00:00:00', // falls back to payment_deadline
        'service_text' => 'Razvoj programske opreme',
    ]);
    $this->assertDatabaseHas(InvoiceItem::class, [
        'title' => 'Razvoj',
        'description' => 'Backend',
        'price' => 1200.50,
        'quantity' => 2,
    ]);
});

it('reuses an existing customer with the same vat number', function () {
    InvoiceExtractor::fake([
        fakeInvoicePayload(['invoice' => ['number' => 1]]),
        fakeInvoicePayload(['invoice' => ['number' => 2]]),
    ]);
    $path = makeImportDir(['2025' => ['a.pdf', 'b.pdf']]);

    $this->artisan('invoices:import', ['path' => $path])->assertSuccessful();

    expect(Customer::count())->toBe(1);
    expect(Invoice::count())->toBe(2);
});

it('overwrites an existing invoice with the same number and period', function () {
    $period = AccountingPeriod::factory()->create(['year' => 2025]);
    $customer = Customer::factory()->create();
    $existing = Invoice::factory()
        ->for($period, 'accountingPeriod')
        ->for($customer)
        ->create(['number' => 7]);
    $staleItem = InvoiceItem::factory()->for($existing)->create(['title' => 'Stale']);

    InvoiceExtractor::fake([fakeInvoicePayload()]);
    $path = makeImportDir(['2025' => ['sample.pdf']]);

    $this->artisan('invoices:import', ['path' => $path])->assertSuccessful();

    expect(Invoice::where('accounting_period_id', $period->id)->where('number', 7)->count())->toBe(1);
    $this->assertDatabaseMissing(InvoiceItem::class, ['id' => $staleItem->id, 'deleted_at' => null]);
    $this->assertDatabaseHas(InvoiceItem::class, ['title' => 'Razvoj']);
});

it('skips folders that are not a 4-digit year', function () {
    InvoiceExtractor::fake([fakeInvoicePayload()]);
    $path = makeImportDir(['misc' => ['note.pdf'], '2025' => ['sample.pdf']]);

    $this->artisan('invoices:import', ['path' => $path])->assertSuccessful();

    expect(AccountingPeriod::pluck('year')->all())->toBe([2025]);
    expect(Invoice::count())->toBe(1);
});

it('does not write anything in dry-run mode', function () {
    InvoiceExtractor::fake([fakeInvoicePayload()]);
    $path = makeImportDir(['2025' => ['sample.pdf']]);

    $this->artisan('invoices:import', ['path' => $path, '--dry-run' => true])->assertSuccessful();

    expect(Invoice::count())->toBe(0);
    expect(Customer::count())->toBe(0);
});

it('fails when the path is not a directory', function () {
    $this->artisan('invoices:import', ['path' => '/no/such/dir'])->assertFailed();
});
