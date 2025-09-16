<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Services\Cart\Data\CreateCartData;

class CartService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function createCart(CreateCartData $data): Cart
    {
        return Cart::create($data->toArray());
    }
}
