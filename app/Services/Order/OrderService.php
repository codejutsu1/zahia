<?php

namespace App\Services\Order;

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Services\Order\Actions\CreateOrderAction;
use App\Services\Order\Data\CreateOrderData;
use Illuminate\Support\Str;

class OrderService
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        protected CreateOrderAction $createOrderAction,
    ) {}

    public function createOrder(CreateOrderData $data): Order
    {
        return $this->createOrderAction->execute($data);
    }

    public function notifyVendor(Order $order): void
    {
        // $vendor = $order->cart->vendor;

        // $vendor->notify(new OrderCreated($order));
    }

    public function repeatOrder(Order $order): Cart
    {
        /** @var Cart $cart */
        $cart = $order->cart;

        $newCart = Cart::create([
            'user_id' => $order->user_id,
            'vendor_id' => $cart->vendor_id,
            'status' => CartStatus::ACTIVE,
        ]);

        /** @phpstan-ignore-next-line */
        $cartItemData = $cart->items->map(function (CartItem $cartItem) use ($newCart) {
            return [
                'uuid' => Str::uuid(),
                'cart_id' => $newCart->id,
                'product_id' => $cartItem->product_id,
                'quantity' => $cartItem->quantity,
                'is_addon' => $cartItem->is_addon,
                'status' => $cartItem->status,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

        CartItem::insert($cartItemData);

        return $newCart;
    }

    public function scheduleOrder(Order $order): void
    {
        //
    }

    public function cancelOrder(Order $order): void
    {
        //
    }
}
