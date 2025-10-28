<?php

namespace App\Prism\Tools\Vendor\Product;

use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Product\ProductService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Tool;

class DeleteProductTool
{
    public static function make(User $user): Tool
    {
        return (new Tool)
            ->as('delete_product')
            ->for('Deletes a product, always ask for confirmation before the deleting the product. Make sure the user is aware its an irreversible action.')
            ->withObjectParameter(
                'product',
                'The product parameters',
                [
                    new StringSchema('name', 'The name of the product'),
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

                    DB::transaction(function () use ($productModel) {
                        app(ProductService::class)->deleteProduct($productModel);
                    });

                    return 'Product deleted successfully, do you want to list or delete another product?';
                } catch (\Exception $e) {
                    Log::error('Error deleting product: '.$e->getMessage());

                    return 'Error deleting product, please try again.';
                }
            });
    }
}
