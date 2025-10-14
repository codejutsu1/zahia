<?php

namespace App\Prism\Tools\Product;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Tool;

class ListProductsTool
{
    public static function make(User $user): Tool
    {
        return (new Tool)
            ->as('list_products')
            ->for('List all products with a name and can be filtered by vendor name and/or price')
            ->withObjectParameter(
                'product',
                'The product parameters',
                [
                    new StringSchema('name', 'The name of the product'),
                    new StringSchema('price', 'The price of the product'),
                    new StringSchema('vendor_name', 'The name of the vendor'),
                ],
                requiredFields: [
                    'name',
                ]
            )
            ->using(function (array $product) {
                try {
                    $productName = data_get($product, 'name');
                    $vendorName = data_get($product, 'vendor_name');
                    $price = data_get($product, 'price');

                    $products = Product::where('name', 'like', '%'.$productName.'%')
                        ->when($vendorName, function ($query) use ($vendorName) {
                            $query->whereHas('vendor', function ($q) use ($vendorName) {
                                $q->where('name', 'like', '%'.$vendorName.'%');
                            });
                        })
                        ->when($price, function ($query) use ($price) {
                            if (preg_match('/^(<=?|>=?)(\d+(?:\.\d+)?)$/', trim($price), $matches)) {
                                $operator = $matches[1];
                                $value = (float) $matches[2];
                                $query->where('price', $operator, $value);
                            } else {
                                $query->where('price', $price);
                            }
                        })
                        ->where('status', ProductStatus::ACTIVE)
                        ->get();

                    $message = '';

                    $products->groupBy('vendor.name')->each(function ($vendorProducts, $vendorName) use (&$message) {
                        $message .= "🍴 *{$vendorName}*\n";

                        foreach ($vendorProducts as $product) {
                            /** @phpstan-ignore-next-line */
                            $message .= "📦 {$product->name} - 💰 ₦".number_format($product->price, 2)."\n";
                        }

                        $message .= "\n";
                    });

                    return $message;
                } catch (\Exception $e) {
                    Log::error('Error listing products: '.$e->getMessage());

                    return 'Error listing products';
                }
            });
    }
}
