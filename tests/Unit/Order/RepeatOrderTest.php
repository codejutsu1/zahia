<?php

use App\Enums\CartStatus;
use App\Enums\OrderStatus;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Order\OrderService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->vendor = Vendor::factory()->create(['name' => 'Test Vendor']);

    $this->cart = Cart::factory()->create([
        'user_id' => $this->user->id,
        'vendor_id' => $this->vendor->id,
    ]);

    $this->order = Order::factory()->create([
        'user_id' => $this->user->id,
        'cart_id' => $this->cart->id,
        'order_id' => 'ORD12345',
        'status' => OrderStatus::PENDING,
    ]);

    $product = Product::factory()->create([
        'name' => 'Jollof Rice',
        'price' => 1500.00,
        'vendor_id' => $this->vendor->id,
    ]);

    OrderItem::factory()->create([
        'order_id' => $this->order->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'price' => 1500.00,
    ]);
});

it('repeats an order successfully', function () {
    $cart = app(OrderService::class)->repeatOrder($this->order);

    expect($cart)->not->toBeNull();
    expect($cart->user_id)->toBe($this->user->id);
    expect($cart->vendor_id)->toBe($this->vendor->id);
    expect($cart->status)->toBe(CartStatus::ACTIVE);
});
