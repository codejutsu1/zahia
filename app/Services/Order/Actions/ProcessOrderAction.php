<?php

namespace App\Services\Order\Actions;

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Mail\OrderCreated;
use App\Models\Order;
use App\Models\Product;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\Mail;

class ProcessOrderAction
{
    public function execute(Order $order): void
    {
        /* @phpstan-ignore-next-line */
        if ($order->status !== OrderStatus::PENDING) {
            return;
        }

        /* @phpstan-ignore-next-line */
        $this->purchaseOrder($order);

        $this->updateOrderStatus($order);

        $this->deductProductQuantity($order);

        $this->notifyCustomer($order);
    }

    public function purchaseOrder(Order $order): void
    {
        $walletService = app(WalletService::class);

        $walletService->purchase(
            /* @phpstan-ignore-next-line */
            wallet: $order->user->wallet,
            /* @phpstan-ignore-next-line */
            amount: $order->total_amount,
        );
    }

    public function updateOrderStatus(Order $order): void
    {
        $order->update([
            'status' => OrderStatus::PROCESSING,
        ]);
    }

    public function deductProductQuantity(Order $order): void
    {
        $order->load('items.product');

        $products = Product::whereIn('id', $order->items->pluck('product_id'))->get();

        $productsToUpdate = [];

        foreach ($order->items as $item) {
            /* @phpstan-ignore-next-line */
            $product = $products->firstWhere('id', $item->product_id);

            $quantityBefore = $product->quantity;
            /* @phpstan-ignore-next-line */
            $quantityAfter = $quantityBefore - $item->quantity;

            /* @phpstan-ignore-next-line */
            $item->status = match (true) {
                /* @phpstan-ignore-next-line */
                $product->status == ProductStatus::UNAVAILABLE => OrderItemStatus::UNAVAILABLE,
                /* @phpstan-ignore-next-line */
                $product->status == ProductStatus::OUT_OF_STOCK => OrderItemStatus::OUT_OF_STOCK,
                /* @phpstan-ignore-next-line */
                $product->status == ProductStatus::ACTIVE => OrderItemStatus::AVAILABLE,
                default => OrderItemStatus::AVAILABLE,
            };

            /* @phpstan-ignore-next-line */
            if ($item->status == OrderItemStatus::AVAILABLE) {
                /* @phpstan-ignore-next-line */
                $product->decrement('quantity', $item->quantity);

                /* @phpstan-ignore-next-line */
                if ($product->quantity === 0) {
                    $product->update([
                        'status' => ProductStatus::OUT_OF_STOCK,
                    ]);
                }
            }

            $item->save();
        }
    }

    public function notifyCustomer(Order $order): void
    {
        /* @phpstan-ignore-next-line */
        Mail::to($order->user->email)->send(new OrderCreated($order));
    }
}
