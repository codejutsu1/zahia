<?php

namespace App\Services\Cart\Data;

use App\Enums\CartItemStatus;
use App\Models\Product;
use Spatie\LaravelData\Data;

class CreateCartItemData extends Data
{
    public function __construct(
        public readonly Product $product,
        public readonly int $quantity,
        public readonly bool $isAddon,
        public readonly CartItemStatus $status = CartItemStatus::ACTIVE,
    ) {}
}
