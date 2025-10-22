<?php

namespace App\Services\Order\Actions;

use App\Enums\CartStatus;
use App\Enums\OrderItemStatus;
use App\Jobs\Order\NotifyVendor;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Order\Data\CreateOrderData;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateOrderAction
{
    public function execute(CreateOrderData $data): Order
    {
        $this->validateData($data);

        $cart = $this->checkForSelectedCartItems($data);

        $totalAmount = $this->getTotalAmount($cart, $data);

        $order = $this->createOrder($cart, $data, $totalAmount);

        $this->createOrderItems($cart, $order, $data);

        $this->initializeTransaction($order, $totalAmount);

        $this->notifyVendor($order);

        return $order;
    }

    protected function validateData(CreateOrderData $data): void
    {
        if ($data->user->id !== $data->cart->user_id) {
            throw new \Exception('This user does not have this cart');
        }
    }

    protected function checkForSelectedCartItems(CreateOrderData $data): Cart
    {
        Log::info($data->cartItemIds);
        if ($data->cartItemIds->isEmpty()) {
            return $data->cart;
        }

        $cart = Cart::create([
            'user_id' => $data->user->id,
            'vendor_id' => $data->cart->vendor_id,
            'status' => CartStatus::ACTIVE,
        ]);

        CartItem::whereIn('id', $data->cartItemIds)->update([
            'cart_id' => $cart->id,
        ]);

        return $cart;
    }

    protected function getTotalAmount(Cart $cart, CreateOrderData $data): int
    {
        $cart->load('items');

        /* @phpstan-ignore-next-line */
        return $cart->items->sum(fn (CartItem $cartItem) => $cartItem->product->price * $cartItem->quantity);
    }

    protected function createOrder(
        Cart $cart,
        CreateOrderData $data,
        int $totalAmount
    ): Order {
        return Order::create([
            'user_id' => $data->user->id,
            'cart_id' => $cart->id,
            'total_amount' => $totalAmount,
            'status' => $data->status,
        ]);
    }

    protected function createOrderItems(Cart $cart, Order $order, CreateOrderData $data): void
    {
        $cart->load('items');
        $cartItem = $cart->items;

        /* @phpstan-ignore-next-line */
        $orderItemData = $cartItem->map(function (CartItem $cartItem) use ($order) {
            return [
                'uuid' => Str::uuid(),
                'order_id' => $order->id,
                'product_id' => $cartItem->product_id,
                'quantity' => $cartItem->quantity,
                /* @phpstan-ignore-next-line */
                'price' => $cartItem->product->price,
                'status' => OrderItemStatus::ACTIVE,
            ];
        })->toArray();

        OrderItem::insert($orderItemData);

        $cart->update([
            'status' => CartStatus::COMPLETED,
        ]);
    }

    protected function initializeTransaction(Order $order, int $totalAmount): void
    {
        $amount = $totalAmount;

        $order->update([
            'account_name' => 'Test Account',
            'account_number' => '1234567890',
            'bank_name' => 'Test Bank',
        ]);
    }

    protected function notifyVendor(Order $order): void
    {
        NotifyVendor::dispatch($order->id);
    }
}
