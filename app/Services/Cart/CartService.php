<?php

namespace App\Services\Cart;

use App\Services\Cart\Actions\CreateCartAction;
use App\Services\Cart\Data\CreateCartData;
use Illuminate\Support\Collection;

class CartService
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        protected CreateCartAction $createCartAction,
    ) {}

    public function createCart(CreateCartData $data): Collection
    {
        return $this->createCartAction->execute($data);
    }
}
