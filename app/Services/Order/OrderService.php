<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Services\Order\Actions\CreateOrderAction;
use App\Services\Order\Data\CreateOrderData;

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
}
