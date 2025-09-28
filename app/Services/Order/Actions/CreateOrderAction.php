<?php

namespace App\Services\Order\Actions;

use App\Enums\OrderItemStatus;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Order\Data\CreateOrderData;
use Illuminate\Support\Str;

class CreateOrderAction
{
    public function execute(CreateOrderData $data): Order
    {
        $this->validateData($data);

        $totalAmount = $this->getTotalAmount($data);

        $order = $this->createOrder($data, $totalAmount);

        $this->createOrderItems($order, $data);

        return $order;
    }

    protected function validateData(CreateOrderData $data): void
    {
        if ($data->user->id !== $data->cart->user_id) {
            throw new \Exception('This user does not have this cart');
        }
    }

    protected function getTotalAmount(CreateOrderData $data): int
    {
        $data->cart->load('items');

        /* @phpstan-ignore-next-line */
        return $data->cart->items->sum(fn (CartItem $cartItem) => $cartItem->product->price * $cartItem->quantity);
    }

    protected function createOrder(CreateOrderData $data, int $totalAmount): Order
    {
        return Order::create([
            'user_id' => $data->user->id,
            'cart_id' => $data->cart->id,
            'total_amount' => $totalAmount,
            'status' => $data->status,
        ]);
    }

    protected function createOrderItems(Order $order, CreateOrderData $data): void
    {
        $data->cart->load('items');
        $cartItem = $data->cart->items;

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
    }
}
