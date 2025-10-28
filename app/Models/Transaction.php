<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Traits\HasUuidColumn;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionFactory> */
    use HasFactory;

    use HasUuidColumn;

    protected $fillable = [
        'uuid',
        'order_id',
        'amount',
        'currency',
        'reference',
        'payment_method',
        'payment_status',
        'payment_id',
        'payment_url',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => TransactionStatus::class,
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
