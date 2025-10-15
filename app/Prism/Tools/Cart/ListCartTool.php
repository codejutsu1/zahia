<?php

namespace App\Prism\Tools\Cart;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Tool;

class ListCartTool
{
    public static function make(User $user): Tool
    {
        return (new Tool)
            ->as('list_carts')
            ->for('List all carts for a user, can be filtered by vendor name which is optional, normally not needed. Display all cart items with the vendor name and the product name and quantity.')
            ->withObjectParameter(
                'carts',
                'The cart parameters',
                [
                    new StringSchema('vendor_name', 'The vendor name', false),
                ],
            )
            ->using(function (array $carts) use ($user) {
                try {
                    $vendorName = $carts['vendor_name'] ?? null;

                    $message = '';

                    $carts = Cart::with('items.product.vendor')
                        ->where('user_id', $user->id)
                        ->when($vendorName, function ($query) use ($vendorName) {
                            $query->whereHas('items.product.vendor', function ($q) use ($vendorName) {
                                $q->where('name', $vendorName);
                            });
                        })
                        ->get();

                    $allItems = $carts->flatMap->items;

                    if ($allItems->isEmpty()) {
                        return 'Your cart is empty.';
                    }

                    $overallTotal = 0;

                    $allItems->groupBy('product.vendor.name')->each(function ($items, $vendorName) use (&$message, &$overallTotal) {
                        $message .= "🍴 *{$vendorName}*\n";

                        $vendorTotal = 0;

                        foreach ($items as $item) {
                            $product = $item->product;
                            $quantity = $item->quantity;
                            $price = $product->price;
                            $total = $price * $quantity;

                            $vendorTotal += $total;

                            $message .= "📦 {$product->name} (₦".number_format($product->price, 2)." x{$item->quantity}) - 💰 ₦".number_format($total, 2)."\n";
                        }

                        $overallTotal += $vendorTotal;

                        $message .= '🧾 *Subtotal:* ₦'.number_format($vendorTotal, 2)."\n\n";
                    });

                    $message .= '💳 *Cart Total:* ₦'.number_format($overallTotal, 2)."\n";

                    $message .= "\n";
                    $message .= "\n";

                    $message .= 'Do you want to checkout or add more items to your cart?';

                    return $message;

                } catch (\Exception $e) {
                    Log::error('Error listing carts: '.$e->getMessage());

                    return 'Error listing carts';
                }
            });
    }
}
