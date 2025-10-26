<?php

namespace App\Prism\Tools\Vendor\Product;

use App\Enums\ProductType;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Product\Data\CreateProductData;
use App\Services\Product\ProductService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Schema\BooleanSchema;
use Prism\Prism\Schema\EnumSchema;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Tool;

class CreateProductTool
{
    public static function make(User $user): Tool
    {
        return (new Tool)
            ->as('create_product')
            ->for('Creates a product, this tool creates a product, after the user confirms he or she wants to create a product.')
            ->withObjectParameter(
                'product',
                'The cart parameters',
                [
                    new StringSchema('name', 'The name of the product'),
                    new NumberSchema('price', 'The price of the product'),
                    new StringSchema('description', 'The name of the product'),
                    new NumberSchema('quantity', 'The quantity of the product'),
                    new EnumSchema('type', 'The type of the product', ProductType::toArray()),
                    new BooleanSchema('is_addon', 'Whether the product is an addon'),
                ],
                requiredFields: [
                    'name',
                    'description',
                    'type',
                    'price',
                ]
            )
            ->using(function (array $product) use ($user) {
                try {
                    $vendor = Vendor::where('user_id', $user->id)->first();

                    if (! $vendor) {
                        return 'You are not a vendor.';
                    }

                    $product['vendor_id'] = $vendor->id;

                    $createProductData = CreateProductData::from($product);

                    DB::transaction(function () use ($createProductData) {
                        app(ProductService::class)->createProduct($createProductData);
                    });

                    return 'Product created successfully, do you want to list or create another product?';
                } catch (\Exception $e) {
                    Log::error('Error creating product: '.$e->getMessage());

                    return 'Error creating product, please try again.';
                }
            });
    }
}
