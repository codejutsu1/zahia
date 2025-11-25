<?php

namespace App\Models;

use App\Enums\TransactionFlow;
use App\Enums\TransactionPaymentProvider;
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
        'wallet_id',
        'order_id',
        'amount',
        'currency',
        'reference',
        'payment_method',
        'payment_status',
        'flow',
        'payment_id',
        'payment_url',
        'status',
        'payload',
        'completed_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TransactionStatus::class,
            'flow' => TransactionFlow::class,
            'payment_provider' => TransactionPaymentProvider::class,
            'payload' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function finalStatus(): bool
    {
        /* @phpstan-ignore-next-line */
        return in_array(
            $this->status,
            [TransactionStatus::Processed, TransactionStatus::Failed],
            true
        );
    }

    public function isProcessed(): bool
    {
        /* @phpstan-ignore-next-line */
        return $this->status == TransactionStatus::Processed;
    }

    public function isFailed(): bool
    {
        /* @phpstan-ignore-next-line */
        return $this->status == TransactionStatus::Failed;
    }
}
