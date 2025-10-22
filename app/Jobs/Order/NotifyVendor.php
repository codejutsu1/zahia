<?php

namespace App\Jobs\Order;

use App\Facade\Chatbot;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class NotifyVendor implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $orderId) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $order = Order::with('cart.vendor')->find($this->orderId);

        if (! $order) {
            return;
        }

        /** @phpstan-ignore-next-line */
        $vendor = $order->cart->vendor;

        /** @phpstan-ignore-next-line */
        $status = ucwords($order->status->value);

        $message = "Hello {$vendor->name},\n"
        ."You have a new order.\n"
        ."Order ID: {$order->order_id}\n"
        .'Total Amount: ₦'.number_format((int) $order->total_amount, 2)."\n"
        ."Status: {$status}\n"
        ."Created At: {$order->created_at}\n\n"
        ."Link to the order: https://zah.ng/orders/{$order->id}";

        Log::info($message);
        //    Chatbot::sendMessage($message);
    }
}
