<?php

namespace App\Prism\Tools\Vendor\Product;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Tool;

class ListProductsTool
{
    public static function make(User $user): Tool
    {
        return (new Tool)
            ->as('list_products')
            ->for('List all products of a vendor, no need to ask the vendor for an input, just list the products with the emoji and not in a single line.')
            ->using(function () use ($user) {
                try {
                    $vendor = Vendor::firstwhere('user_id', $user->id);

                    if (! $vendor) {
                        return 'You are not a vendor.';
                    }

                    $products = Product::whereBelongsTo($vendor)
                        ->where('status', ProductStatus::ACTIVE)
                        ->get();

                    if ($products->isEmpty()) {
                        return 'You do not have any active products yet.';
                    }

                    $message = '';

                    $message = $products
                        ->map(function ($product, $index) {
                            /** @phpstan-ignore-next-line */
                            $status = ucwords($product->status->value);
                            /** @phpstan-ignore-next-line */
                            $type = ucwords($product->type->value);

                            $name = $product->name;
                            /** @phpstan-ignore-next-line */
                            $price = '₦'.number_format($product->price, 2);
                            $quantity = $product->quantity ?? 'N/A';

                            return '🔢 Product #'.
                                ($index + 1).
                                "
                                📦 Name: {$name}
                                💰 Price: {$price}
                                🔢 Qty: {$quantity}
                                ✅ Status: {$status}
                                🏷️ Type: {$type}";
                        })
                        ->implode("\n\n-----------------------\n\n");

                    return $message;
                } catch (\Exception $e) {
                    Log::error('Error listing products: '.$e->getMessage());

                    return 'Error listing products';
                }
            });
    }
}
