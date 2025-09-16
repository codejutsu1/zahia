<?php

namespace App\Models;

use App\Enums\CartItemStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    /** @use HasFactory<\Database\Factories\CartItemFactory> */
    use HasFactory;

    protected $fillable = [
        'uuid',
        'cart_id',
        'product_id',
        'quantity',
        'is_addon',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'is_addon' => 'boolean',
            'status' => CartItemStatus::class,
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
