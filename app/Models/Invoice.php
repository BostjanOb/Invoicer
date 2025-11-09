<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'accounting_period_id',
        'number',
        'status',
        'issue_date',
        'payment_deadline',
        'paid_at',
        'service_text',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function accountingPeriod(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function total(): float
    {
        return $this->items->sum(fn (InvoiceItem $item) => $item->price * $item->quantity);
    }

    public function fullNumber(): string
    {
        return str_pad($this->number, 3, '0', STR_PAD_LEFT).'-'.$this->accountingPeriod->year;
    }

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'payment_deadline' => 'date',
            'paid_at' => 'date',
            'status' => InvoiceStatus::class,
        ];
    }
}
