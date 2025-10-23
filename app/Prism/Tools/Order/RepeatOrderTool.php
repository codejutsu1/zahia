<?php

namespace App\Prism\Tools\Order;

use App\Models\Order;
use App\Models\User;
use App\Services\Order\OrderService;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Tool;

class RepeatOrderTool
{
    public static function make(User $user): Tool
    {
        return (new Tool)
            ->as('repeat_order')
            ->for('Repeat an order, this tool repeats an order, after the user confirms he or she wants to repeat an order, call the repeat order tool with the order id.')
            ->withObjectParameter(
                'order',
                'The order parameters',
                [
                    new StringSchema('id', 'The order ID'),
                ],
            )
            ->using(function (array $order) {
                try {
                    $order = Order::firstWhere('order_id', strtoupper($order['id']));

                    if (! $order) {
                        return 'The order ID is incorrect, please try again.';
                    }

                    $cart = app(OrderService::class)->repeatOrder($order);

                    $cart->load('items.product.vendor');

                    $message = '';

                    /* @phpstan-ignore-next-line */
                    $message .= "🍴 *{$cart->vendor->name}*\n";

                    $total = 0;

                    $cart->items->each(function ($item) use (&$message, &$total) {
                        /* @phpstan-ignore-next-line */
                        $total += $item->product->price * $item->quantity;

                        /* @phpstan-ignore-next-line */
                        $message .= "📦 {$item->product->name} (₦".number_format($item->product->price, 2)." x{$item->quantity}) = 💰 ₦".number_format($item->product->price * $item->quantity, 2)."\n\n";
                    });

                    $message .= '💳 *Total Amount:* ₦'.number_format($total, 2)."\n\n";

                    $message .= 'Do you want to checkout or add more items to your cart?';

                    return $message;
                } catch (\Exception $e) {
                    Log::error('Error listing orders: '.$e->getMessage());

                    return 'Error repeating order';
                }
            });
    }
}
