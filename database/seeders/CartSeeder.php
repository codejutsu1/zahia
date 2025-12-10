<?php

namespace Database\Seeders;

use App\Enums\CartItemStatus;
use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class CartSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vendor = Vendor::find(5);

        $product = Product::where('vendor_id', $vendor->id)->first();

        $cart = Cart::create([
            'vendor_id' => $vendor->id,
            'user_id' => User::first()->id,
            'status' => CartStatus::ACTIVE,
        ]);

        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'is_addon' => false,
            'status' => CartItemStatus::ACTIVE,
        ]);
    }
}
