<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Cart;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'cart_id' => Cart::factory(),
            'order_id' => 'ORD12345',
            'total_amount' => 3000,
            'status' => OrderStatus::PENDING,
            'account_name' => 'Test Account',
            'account_number' => '1234567890',
            'bank_name' => 'Test Bank',
        ];
    }
}
