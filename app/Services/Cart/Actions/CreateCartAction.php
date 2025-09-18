<?php

namespace App\Services\Cart\Actions;

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Services\Cart\Data\CreateCartData;
use App\Services\Cart\Data\CreateCartItemData;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CreateCartAction
{
    /** @return Collection<Cart> */
    public function execute(CreateCartData $data): Collection
    {
        $carts = $this->createCart($data);

        $this->createCartItems($carts, $data);

        return $carts;
    }

    /** @return Collection<Cart> */
    protected function createCart(CreateCartData $data): Collection
    {
        $carts = collect();

        $vendorIds = $data->cartItems
            ->map(fn (CreateCartItemData $i) => $i->product->vendor_id)
            ->unique();

        $vendorIds->each(function ($vendorId) use (&$carts, $data) {
            $cart = Cart::firstOrCreate([
                'user_id' => $data->user_id,
                'vendor_id' => $vendorId,
                'status' => CartStatus::ACTIVE,
            ]);

            $carts->push($cart);
        });

        return $carts;
    }

    protected function createCartItems(Collection $carts, CreateCartData $data): void
    {
        $now = now();

        $cartItemData = $data->cartItems->map(function (CreateCartItemData $item) use ($carts, $now) {
            $cart = $carts->firstWhere('vendor_id', $item->product->vendor_id);

            return [
                'uuid' => Str::uuid(),
                'cart_id' => $cart->id,
                'product_id' => $item->product->id,
                'quantity' => $item->quantity,
                'is_addon' => (bool) $item->isAddon,
                'status' => $item->status,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->toArray();

        CartItem::insert($cartItemData);
    }
}
