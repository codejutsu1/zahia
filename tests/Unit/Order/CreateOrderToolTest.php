<?php

use App\Enums\CartStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\DeliveryAddress;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Order\Data\CreateOrderData;
use App\Services\Order\OrderService;

beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'customer@example.com',
    ]);

    $this->deliveryAddress = DeliveryAddress::factory()->create([
        'user_id' => $this->user->id,
        'is_main' => true,
    ]);

    $this->vendor = Vendor::factory()->create([
        'name' => 'Tasty Foods',
    ]);

    $this->products = collect([
        Product::factory()->create([
            'name' => 'Jollof Rice',
            'price' => 1500.00,
            'vendor_id' => $this->vendor->id,
            'status' => ProductStatus::ACTIVE,
        ]),
        Product::factory()->create([
            'name' => 'Fried Rice',
            'price' => 1200.00,
            'vendor_id' => $this->vendor->id,
            'status' => ProductStatus::ACTIVE,
        ]),
    ]);

    $this->cart = Cart::factory()->create([
        'user_id' => $this->user->id,
        'vendor_id' => $this->vendor->id,
        'status' => 'active',
    ]);

    foreach ($this->products as $product) {
        CartItem::factory()->create([
            'cart_id' => $this->cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
    }
});

it('creates an order successfully with valid data', function () {
    $orderData = CreateOrderData::from([
        'user' => $this->user,
        'cart' => $this->cart,
        'status' => OrderStatus::PENDING,
        'cartItemIds' => $this->cart->items->pluck('id'),
    ]);
    $order = app(OrderService::class)->createOrder($orderData);

    expect(Order::count())->toBe(1);

    $order = Order::first();
    expect($order->user_id)->toBe($this->user->id);
    expect($order->status)->toBe(OrderStatus::PENDING);
});

it('creates order with correct total amount calculation', function () {
    $orderData = CreateOrderData::from([
        'user' => $this->user,
        'cart' => $this->cart,
        'status' => OrderStatus::PENDING,
        'cartItemIds' => $this->cart->items->pluck('id'),
    ]);
    $order = app(OrderService::class)->createOrder($orderData);

    expect($order)->not->toBeNull();
    expect($order->total_amount)->toBeGreaterThan(0);
    expect($order->total_amount)->toBe(2700);
});

it('returns error when user has no email', function () {
    $userWithoutEmail = User::factory()->create([
        'email' => null,
    ]);

    $cart = Cart::factory()->create([
        'user_id' => $userWithoutEmail->id,
        'vendor_id' => $this->vendor->id,
        'status' => 'active',
    ]);

    CartItem::factory()->create([
        'cart_id' => $cart->id,
        'product_id' => $this->products->first()->id,
        'quantity' => 1,
    ]);

    $orderData = CreateOrderData::from([
        'user' => $userWithoutEmail,
        'cart' => $cart,
        'status' => OrderStatus::PENDING,
        'cartItemIds' => $cart->items->pluck('id'),
    ]);
    expect(
        fn () => app(OrderService::class)->createOrder($orderData)
    )->toThrow(Exception::class);
    expect(Order::count())->toBe(0);
});

it('creates order with pending status', function () {
    $orderData = CreateOrderData::from([
        'user' => $this->user,
        'cart' => $this->cart,
        'status' => OrderStatus::PENDING,
        'cartItemIds' => $this->cart->items->pluck('id'),
    ]);
    $order = app(OrderService::class)->createOrder($orderData);

    $order = Order::first();
    expect($order->status)->toBe(OrderStatus::PENDING);
});

it('only creates order for active carts', function () {
    // Mark cart as inactive
    $this->cart->update(['status' => CartStatus::COMPLETED]);

    $orderData = CreateOrderData::from([
        'user' => $this->user,
        'cart' => $this->cart,
        'status' => OrderStatus::PENDING,
        'cartItemIds' => $this->cart->items->pluck('id'),
    ]);

    expect(
        fn () => app(OrderService::class)->createOrder($orderData)
    )->toThrow(Exception::class);

    expect(Order::count())->toBe(0);
});

it('creates order for the correct user', function () {
    $anotherUser = User::factory()->create([
        'email' => 'another@example.com',
    ]);

    $orderData = CreateOrderData::from([
        'user' => $this->user,
        'cart' => $this->cart,
        'status' => OrderStatus::PENDING,
        'cartItemIds' => $this->cart->items->pluck('id'),
    ]);
    $order = app(OrderService::class)->createOrder($orderData);

    expect($order->user_id)->toBe($this->user->id);
    expect($order->user_id)->not->toBe($anotherUser->id);
});

it('includes all cart items in order creation', function () {

    $orderData = CreateOrderData::from([
        'user' => $this->user,
        'cart' => $this->cart,
        'status' => OrderStatus::PENDING,
        'cartItemIds' => $this->cart->items->pluck('id'),
    ]);
    $order = app(OrderService::class)->createOrder($orderData);

    expect($order->items->count())->toBe(2);
});
