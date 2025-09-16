<?php

namespace App\Services\Cart\Data;

use App\Enums\CartStatus;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class CreateCartData extends Data
{
    public function __construct(
        public readonly int $vendor_id,
        public readonly int $user_id,
        /** @var Collection<CreateCartItemData> */
        public readonly Collection $cartItems,
        public readonly CartStatus $status = CartStatus::ACTIVE,
    ) {}
}
