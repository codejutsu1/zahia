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

                    $message = '';

                    $products->each(function ($product) use (&$message) {
                        /** @phpstan-ignore-next-line */
                        $message .= "📦 {$product->name} - 💰 ₦".number_format($product->price, 2)."\n";
                    });

                    return $message;
                } catch (\Exception $e) {
                    Log::error('Error listing products: '.$e->getMessage());

                    return 'Error listing products';
                }
            });
    }
}
