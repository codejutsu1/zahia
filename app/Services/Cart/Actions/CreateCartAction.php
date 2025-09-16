<?php

namespace App\Services\Cart\Actions;

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Services\Cart\Data\CreateCartData;
use App\Services\Cart\Data\CreateCartItemData;

class CreateCartAction
{
    public function execute(CreateCartData $data): Cart
    {
        $cart = $this->createCart($data);

        $this->createCartItems($cart, $data);

        return $cart;
    }

    protected function createCart(CreateCartData $data): Cart
    {
        $cart = Cart::where([
            'vendor_id' => $data->vendor_id,
            'status' => CartStatus::ACTIVE,
        ]);

        if ($cart->exists()) {
            return $cart->first();
        }

        return Cart::create([
            'vendor_id' => $data->vendor_id,
            'user_id' => $data->user_id,
            'status' => $data->status,
        ]);
    }

    protected function createCartItems(Cart $cart, CreateCartData $data): void
    {
        $cartItemData = $data->cartItems->map(function (CreateCartItemData $item) use ($cart) {
            return [
                'cart_id' => $cart->id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'is_addon' => $item->isAddon,
                'status' => $item->status,
            ];
        })->toArray();

        CartItem::insert($cartItemData);
    }
}
