<?php

use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Cart\CartService;
use App\Services\Cart\Data\CreateCartData;
use App\Services\Cart\Data\CreateCartItemData;

it('creates cart successfully with multiple products', function () {
    $user = User::factory()->create();

    $vendor = Vendor::factory()->create(['name' => 'Pizza Palace']);

    $product1 = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'name' => 'Margherita Pizza',
        'price' => 2500.00,
        'is_addon' => false,
    ]);
    $product2 = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'name' => 'Pepperoni Pizza',
        'price' => 3000.00,
        'is_addon' => false,
    ]);

    $cartItemData1 = CreateCartItemData::from([
        'product' => $product1,
        'quantity' => 2,
        'isAddon' => false,
    ]);

    $cartItemData2 = CreateCartItemData::from([
        'product' => $product2,
        'quantity' => 1,
        'isAddon' => false,
    ]);

    $cartData = CreateCartData::from([
        'user_id' => $user->id,
        'cartItems' => collect([$cartItemData1, $cartItemData2]),
    ]);

    $result = app(CartService::class)->createCart($cartData);

    expect($result->count())->toBe(1);
    expect($result->first()->items->count())->toBe(2);
});

it('creates cart with products from multiple vendors', function () {
    $user = User::factory()->create();

    $vendor1 = Vendor::factory()->create(['name' => 'Pizza Palace']);
    $vendor2 = Vendor::factory()->create(['name' => 'Burger Joint']);

    $product1 = Product::factory()->create([
        'vendor_id' => $vendor1->id,
        'name' => 'Margherita Pizza',
        'price' => 2500.00,
        'is_addon' => false,
    ]);
    $product2 = Product::factory()->create([
        'vendor_id' => $vendor2->id,
        'name' => 'Classic Burger',
        'price' => 1500.00,
        'is_addon' => false,
    ]);

    $cartItemData1 = CreateCartItemData::from([
        'product' => $product1,
        'quantity' => 1,
        'isAddon' => false,
    ]);

    $cartItemData2 = CreateCartItemData::from([
        'product' => $product2,
        'quantity' => 2,
        'isAddon' => false,
    ]);

    $cartData = CreateCartData::from([
        'user_id' => $user->id,
        'cartItems' => collect([$cartItemData1, $cartItemData2]),
    ]);

    $result = app(CartService::class)->createCart($cartData);

    expect($result->count())->toBe(2);
    expect($result->first()->items->count())->toBe(1);
    expect($result->last()->items->count())->toBe(1);
});

it('handles addon products correctly', function () {
    $user = User::factory()->create();

    $vendor = Vendor::factory()->create(['name' => 'Pizza Palace']);

    $mainProduct = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'name' => 'Large Pizza',
        'price' => 3500.00,
        'is_addon' => false,
    ]);
    $addonProduct = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'name' => 'Extra Cheese',
        'price' => 500.00,
        'is_addon' => true,
    ]);

    $cartItemData1 = CreateCartItemData::from([
        'product' => $mainProduct,
        'quantity' => 1,
        'isAddon' => false,
    ]);

    $cartItemData2 = CreateCartItemData::from([
        'product' => $addonProduct,
        'quantity' => 1,
        'isAddon' => true,
    ]);

    $cartData = CreateCartData::from([
        'user_id' => $user->id,
        'cartItems' => collect([$cartItemData1, $cartItemData2]),
    ]);

    $result = app(CartService::class)->createCart($cartData);

    expect($result->count())->toBe(1);
    expect($result->first()->items->count())->toBe(2);
    expect($result->first()->items->firstWhere('product_id', $mainProduct->id)->is_addon)->toBeFalse();
    expect($result->first()->items->firstWhere('product_id', $addonProduct->id)->is_addon)->toBeTrue();
});
