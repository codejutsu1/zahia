<?php

namespace App\Prism\Tools\Order;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Tool;

class CheckoutOrderTool
{
    public static function make(User $user): Tool
    {
        return (new Tool)
            ->as('checkout_order')
            ->for('Getting checkout information, this tool returns details and ask the user for confirmation before proceeding with the creation of order, if the user confirmation is positive, call the create order tool. You can checkout items by passing the names into the array of products.')
            ->withObjectParameter(
                'cart',
                'The cart parameters',
                [
                    new StringSchema('vendor_name', 'The vendor name that belongs to the cart'),
                    new ArraySchema(
                        name: 'products',
                        description: 'The selected products that the user wants to add to the order',
                        items: new ObjectSchema(
                            name: 'product',
                            description: 'A product entry',
                            properties: [
                                new StringSchema('name', 'The product name'),
                            ],
                            requiredFields: ['name']
                        )
                    ),
                ],
                requiredFields: [
                    'vendor_name',
                ]
            )
            ->using(function (array $cart) use ($user) {
                try {
                    $vendorName = $cart['vendor_name'];
                    $productNames = collect($cart['products'])->pluck('name')->toArray();
                    $productNames = array_map('ucwords', $productNames);

                    Log::info(['checkout' => $productNames]);

                    $message = '';

                    $cart = Cart::with([
                        'items' => function ($q) use ($productNames) {
                            $q->whereHas('product', fn (Builder $q2) => $q2->whereIn('name', $productNames))
                                ->with('product.vendor');
                        },
                    ])
                        ->where('user_id', $user->id)
                        ->active()
                        ->whereHas('vendor', function ($query) use ($vendorName) {
                            $query->where('name', $vendorName);
                        })
                        ->first();

                    Log::info($cart->items->pluck('id'));

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
