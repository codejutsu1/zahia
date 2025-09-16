<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Traits\HasUuidColumn;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    use HasUuidColumn;

    protected $fillable = [
        'name',
        'description',
        'price',
        'status',
        'vendor_id',
        'quantity',
        'type',
        'is_addon',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
            'type' => ProductType::class,
            'price' => 'integer',
            'quantity' => 'integer',
            'is_addon' => 'boolean',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
