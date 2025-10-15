<?php

namespace App\Prism\Tools\Order;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Tool;

class CheckoutOrderTool
{
    public static function make(User $user): Tool
    {
        return (new Tool)
            ->as('checkout_order')
            ->for('Getting checkout information, this tool returns details and ask the user for confirmation before proceeding with the creation of order, if the user confirmation is positive, call the create order tool.')
            ->withObjectParameter(
                'cart',
                'The cart parameters',
                [
                    new StringSchema('vendor_name', 'The vendor name that belongs to the cart'),
                ],
                requiredFields: [
                    'vendor_name',
                ]
            )
            ->using(function (array $cart) use ($user) {
                try {
                    $vendorName = $cart['vendor_name'];

                    $message = '';

                    $cart = Cart::with('items.product.vendor')
                        ->where('user_id', $user->id)
                        ->whereHas('vendor', function ($query) use ($vendorName) {
                            $query->where('name', $vendorName);
                        })
                        ->first();

                    $overallTotal = 0;
                    $deliveryFee = random_int(1, 4) * 400;
                    $serviceFee = random_int(1, 4) * 400;

                    /** @phpstan-ignore-next-line */
                    $message .= "🍴 *{$cart->vendor->name}*\n";

                    $cart->items->each(function ($item) use (&$message, &$overallTotal) {
                        /** @phpstan-ignore-next-line */
                        $product = $item->product;

                        /** @phpstan-ignore-next-line */
                        $total = $product->price * $item->quantity;

                        /** @phpstan-ignore-next-line */
                        $message .= "📦 {$product->name} (₦".number_format($product->price, 2)." x{$item->quantity}) = 💰 ₦".number_format($total, 2)."\n\n";

                        $overallTotal += $total;
                    });

                    $subtotal = $overallTotal;
                    $grandTotal = $subtotal + $deliveryFee + $serviceFee;

                    $message .= '🧾 *Subtotal:* ₦'.number_format($subtotal, 2)."\n";
                    $message .= '🚚 *Delivery Fee:* ₦'.number_format($deliveryFee, 2)."\n";
                    $message .= '⚙️ *Service Fee:* ₦'.number_format($serviceFee, 2)."\n";
                    $message .= '💳 *Cart Total:* ₦'.number_format($grandTotal, 2)."\n";

                    return $message;
                } catch (\Exception $e) {
                    Log::error('Error getting checkout information: '.$e->getMessage());

                    return 'Error getting checkout information';
                }
            });
    }
}
