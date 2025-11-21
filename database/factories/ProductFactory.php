<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'price' => fake()->randomFloat(2, 0, 10000),
            'vendor_id' => Vendor::factory(),
            'description' => fake()->sentence(),
            'quantity' => fake()->numberBetween(1, 100),
            'status' => ProductStatus::ACTIVE,
            'type' => ProductType::FOOD,
        ];
    }
}
