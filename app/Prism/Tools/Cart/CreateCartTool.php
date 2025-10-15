<?php

namespace App\Prism\Tools\Cart;

use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Cart\CartService;
use App\Services\Cart\Data\CreateCartData;
use App\Services\Cart\Data\CreateCartItemData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Tool;

class CreateCartTool
{
    public static function make(User $user): Tool
    {
        return (new Tool)
            ->as('create_cart')
            ->for('Create a cart, user can add multiple products to the cart and each cart is unique to a vendor, this is not the same as creating an order!')
            ->withObjectParameter(
                'cart',
                'The cart parameters',
                [
                    new ArraySchema(
                        name: 'cart_items',
                        description: 'The user\'s list of cart items, each cart item has a unique vendor name and an array of product name and quantity',
                        items: new ObjectSchema(
                            name: 'cart_item',
                            description: 'A detailed cart item entry',
                            properties: [
                                new StringSchema('vendor_name', 'The vendor name'),
                                new ObjectSchema(
                                    name: 'product',
                                    description: 'A product entry',
                                    properties: [
                                        new StringSchema('name', 'The product name'),
                                        new StringSchema('quantity', 'The quantity of the product'),
                                    ],
                                    requiredFields: ['name', 'quantity']
                                ),
                            ],
                            requiredFields: ['vendor_name', 'product']
                        )
                    ),
                ],
                requiredFields: [
                    'cart_items',
                ]
            )
            ->using(function (array $cart) use ($user) {
                try {
                    $message = DB::transaction(function () use ($user, $cart) {
                        $items = collect($cart['cart_items']);

                        $vendorNames = $items->pluck('vendor_name');
                        $vendors = Vendor::whereIn('name', $vendorNames)
                            ->get()
                            ->keyBy('name');

                        $products = Product::with('vendor')
                            ->whereIn('vendor_id', $vendors->pluck('id'))
                            ->get()
                            /** @phpstan-ignore-next-line */
                            ->groupBy(fn ($p) => $p->vendor->name);

                        $cartItemData = collect();

                        foreach ($items as $item) {
                            $vendorName = $item['vendor_name'];
                            $productName = ucwords($item['product']['name']);

                            $vendor = $vendors[$vendorName] ?? null;

                            if (! $vendor) {
                                return "$vendorName doesn't exist in our system!, try another vendor?";
                            }

                            $product = $products[$vendorName]->firstWhere('name', $productName) ?? null;

                            if (! $product) {
                                return "$vendorName doesnt have this $productName, try another product?";
                            }

                            $cartItemData->push(
                                CreateCartItemData::from([
                                    'product' => $product,
                                    'quantity' => $item['product']['quantity'],
                                    'isAddon' => (bool) $product->is_addon,
                                ])
                            );
                        }
                        $cartData = CreateCartData::from([
                            'user_id' => $user->id,
                            'cartItems' => $cartItemData,
                        ]);

                        Log::info(['cartData' => $cartData->toArray()]);

                        app(CartService::class)->createCart($cartData);

                        return 'success';
                    });

                    return $message == 'success'
                            ? 'Cart created successfully, do you want to add more products to your cart or checkout?'
                            : $message;
                } catch (\Exception $e) {
                    Log::error('Error creating cart: '.$e->getMessage());

                    return 'Error creating cart';
                }
            });
    }
}
