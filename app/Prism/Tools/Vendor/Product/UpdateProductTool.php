<?php

namespace App\Prism\Tools\Vendor\Product;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Product\Data\UpdateProductData;
use App\Services\Product\ProductService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Schema\BooleanSchema;
use Prism\Prism\Schema\EnumSchema;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Tool;

class UpdateProductTool
{
    public static function make(User $user): Tool
    {
        return (new Tool)
            ->as('update_product')
            ->for('Updates a product, this tool updates a product, after the user confirms he or she wants to update a product.')
            ->withObjectParameter(
                'product',
                'The product parameters',
                [
                    new StringSchema('name', 'The name of the product'),
                    new NumberSchema('price', 'The price of the product'),
                    new StringSchema('description', 'The name of the product'),
                    new NumberSchema('quantity', 'The quantity of the product'),
                    new EnumSchema('type', 'The type of the product', ProductType::toArray()),
                    new BooleanSchema('is_addon', 'Whether the product is an addon'),
                    new EnumSchema('status', 'The status of the product', ProductStatus::toArray()),
                ],
                requiredFields: [
                    'name',
                ]
            )
            ->using(function (array $product) use ($user) {
                try {
                    $vendor = Vendor::firstwhere('user_id', $user->id);

                    if (! $vendor) {
                        return 'You are not a vendor.';
                    }

                    $productModel = Product::whereBelongsTo($vendor)
                        ->where('name', $product['name'])
                        ->first();

                    if (! $productModel) {
                        return 'Product not found doesn\'t exist .';
                    }

                    $product['vendor_id'] = $vendor->id;
                    $product['id'] = $productModel->id;
                    $updateProductData = UpdateProductData::from($product);

                    DB::transaction(function () use ($productModel, $updateProductData) {
                        app(ProductService::class)->updateProduct($productModel, $updateProductData);
                    });

                    return 'Product updated successfully, do you want to list or update another product?';
                } catch (\Exception $e) {
                    Log::error('Error updating product: '.$e->getMessage());

                    return 'Error updating product, please try again.';
                }
            });
    }
}
