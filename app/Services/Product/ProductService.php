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

    public function createProduct(CreateProductData $data)
    {
        return Product::create($data->toArray());
    }
}
