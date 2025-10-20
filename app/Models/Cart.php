<?php

namespace App\Models;

use App\Enums\CartStatus;
use App\Traits\HasUuidColumn;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    /** @use HasFactory<\Database\Factories\CartFactory> */
    use HasFactory;

    use HasUuidColumn;

    protected $fillable = [
        'vendor_id',
        'user_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => CartStatus::class,
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    #[Scope]
    public function scopeActive(Builder $query): Builder
    {
        return $this->where('status', CartStatus::ACTIVE);
    }
}
