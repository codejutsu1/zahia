<?php

namespace App\Prism\Tools\Order;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Tool;

class ListOrdersTool
{
    public static function make(User $user): Tool
    {
        return (new Tool)
            ->as('list_orders')
            ->for('List all orders of a user. The user doesn\'t need to pass any parameters, it will return all orders of the user. After listing the order, ask if I want to repeat the order, if the user says yes, call the repeat order tool.')
            ->withObjectParameter(
                'orders',
                'The orders parameters',
                [
                    new StringSchema('order_id', 'The order ID'),
                ],
            )
            ->using(function (array $orders) use ($user) {
                try {
                    $message = '';
                    Log::info('Listing orders for user: '.$user->id);

                    $orders = Order::with('cart.vendor:id,name')
                        ->withCount('items')
                        ->whereBelongsTo($user)
                        ->get();

                    $orders->each(function ($order) use (&$message) {
                        /** @phpstan-ignore-next-line  */
                        $status = ucwords($order->status->value);
                        $date = $order->created_at->format('jS F Y, H:i');

                        $message .= "💳 *Order ID:* {$order->order_id}\n";
                        /** @phpstan-ignore-next-line  */
                        $message .= "💳 *Vendor Name:* {$order->cart->vendor->name}\n";
                        $message .= "💳 *Total Items:* {$order->items_count}\n";
                        $message .= '💳 *Total Amount:* ₦'.number_format((int) $order->total_amount, 2)."\n";
                        $message .= "💳 *Status:* {$status}\n";
                        $message .= "💳 *Created At:* {$date}\n";
                        $message .= "\n\n";
                    });

                    return $message;

                } catch (\Exception $e) {
                    Log::error('Error listing orders: '.$e->getMessage());

                    return 'Error listing orders';
                }
            });
    }
}
