<?php

namespace App\Prism\Tools\Order;

use App\Enums\OrderStatus;
use App\Models\Cart;
use App\Models\User;
use App\Services\Order\Data\CreateOrderData;
use App\Services\Order\OrderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Tool;

class CreateOrderTool
{
    public static function make(User $user): Tool
    {
        return (new Tool)
            ->as('create_order')
            ->for('Creating an order, this tool creates an order, after the user confirms he or she wants to create an order, call the create order tool with the vendor name.')
            ->withObjectParameter(
                'cart',
                'The cart parameters',
                [
                    new StringSchema('vendor_name', 'The vendor name that belongs to the cart'),
                ],
                requiredFields: [
                    'vendor_name',
                ]
            )
            ->using(function (array $cart) use ($user) {
                try {
                    $vendorName = $cart['vendor_name'];

                    $cart = Cart::with('items.product.vendor')
                        ->where('user_id', $user->id)
                        ->whereHas('vendor', function ($query) use ($vendorName) {
                            $query->where('name', $vendorName);
                        })
                        ->first();

                    DB::transaction(function () use ($cart, $user) {
                        $createOrderData = CreateOrderData::from([
                            'user' => $user,
                            'cart' => $cart,
                            'status' => OrderStatus::PENDING,
                        ]);

                        app(OrderService::class)->createOrder($createOrderData);
                    });

                    return 'Order created successfully';
                } catch (\Exception $e) {
                    Log::error('Error creating order: '.$e->getMessage());

                    return 'Error creating order';
                }
            });
    }
}
