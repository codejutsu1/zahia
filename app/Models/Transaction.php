<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionFactory> */
    use HasFactory;

    protected $fillable = [
        'uuid',
        'order_id',
        'amount',
        'currency',
        'reference',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
