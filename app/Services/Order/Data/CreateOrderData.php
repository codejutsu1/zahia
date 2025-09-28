<?php

namespace App\Services\Order\Data;

use App\Enums\OrderStatus;
use App\Models\Cart;
use App\Models\User;
use Spatie\LaravelData\Data;

class CreateOrderData extends Data
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public readonly User $user,
        public readonly Cart $cart,
        public readonly OrderStatus $status = OrderStatus::PENDING,
    ) {}
}
