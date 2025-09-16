<?php

namespace App\Services\Cart\Data;

use App\Enums\CartItemStatus;

class CreateCartItemData
{
    public function __construct(
        public readonly int $cart_id,
        public readonly int $product_id,
        public readonly int $quantity,
        public readonly bool $isAddon,
        public readonly CartItemStatus $status = CartItemStatus::ACTIVE,
    ) {}
}
