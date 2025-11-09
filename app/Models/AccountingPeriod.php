<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingPeriod extends Model
{
    /** @use HasFactory<\Database\Factories\AccountingPeriodFactory> */
    use HasFactory;

    protected $fillable = [
        'year',
        'is_closed',
    ];

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    protected function casts(): array
    {
        return [
            'is_closed' => 'boolean',
        ];
    }
}
