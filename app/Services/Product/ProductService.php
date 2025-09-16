<?php

namespace App\Services\Product;

use App\Models\Product;
use App\Services\Product\Data\CreateProductData;

class ProductService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function createProduct(CreateProductData $data): Product
    {
        return Product::create($data->toArray());
    }

    public function updateProduct(Product $product, CreateProductData $data): Product
    {
        $product->update($data->toArray());

        return $product;
    }

    public function deleteProduct(Product $product): void
    {
        $product->delete();
    }
}
