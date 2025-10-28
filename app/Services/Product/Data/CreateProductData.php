<?php

namespace App\Services\Product\Data;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use Spatie\LaravelData\Data;

class CreateProductData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly int $price,
        public readonly int $vendor_id,
        public readonly bool $is_addon = false,
        public readonly ?int $quantity = null,
        public readonly ?string $description = null,
        public readonly ProductType $type = ProductType::FOOD,
        public readonly ProductStatus $status = ProductStatus::ACTIVE,
    ) {}
}
