<?php

namespace App\Models;

use App\Enums\WalletType;
use App\Enums\WalletStatus;
use App\Traits\HasUuidColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Wallet extends Model
{
    /** @use HasFactory<\Database\Factories\WalletFactory> */
    use HasFactory;

    use HasUuidColumn;

    protected $fillable = [
        'uuid',
        'user_id',
        'balance',
        'status',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'status' => WalletStatus::class,
            'type' => WalletType::class,
            'balance' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
