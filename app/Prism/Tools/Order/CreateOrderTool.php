<?php

namespace App\Prism\Tools\Order;

use App\Enums\OrderStatus;
use App\Models\Cart;
use App\Models\User;
use App\Services\Order\Data\CreateOrderData;
use App\Services\Order\OrderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Tool;

class CreateOrderTool
{
    public static function make(User $user): Tool
    {
        return (new Tool)
            ->as('create_order')
            ->for('Creating an order, this tool creates an order, after the user confirms he or she wants to create an order, call the create order tool with the vendor name. If the user gets an error saying invalid email, ask the user to provide a valid email address and call the update email tool.')
            ->withObjectParameter(
                'cart',
                'The cart parameters',
                [
                    new StringSchema('vendor_name', 'The vendor name that belongs to the cart'),
                    new ArraySchema(
                        name: 'products',
                        description: 'The selected products that the user wants to add to the order',
                        items: new ObjectSchema(
                            name: 'product',
                            description: 'A product entry',
                            properties: [
                                new StringSchema('name', 'The product name'),
                            ],
                            requiredFields: ['name']
                        )
                    ),
                ],
                requiredFields: [
                    'vendor_name',
                ]
            )
            ->using(function (array $cart) use ($user) {
                try {
                    $vendorName = $cart['vendor_name'];
                    $productNames = collect($cart['products'])->pluck('name')->toArray();
                    $productNames = array_map('ucwords', $productNames);

                    $productNames = array_values(array_filter((array) $productNames));

                    $cart = Cart::with([
                        'items' => function ($q) use ($productNames) {
                            $q->whereHas('product', fn (Builder $q2) => $q2->whereIn('name', $productNames))
                                ->with('product.vendor');
                        },
                        'user',
                    ])
                        ->where('user_id', $user->id)
                        ->active()
                        ->whereHas('vendor', function ($query) use ($vendorName) {
                            $query->where('name', $vendorName);
                        })
                        ->first();

                    /** @phpstan-ignore-next-line */
                    if (is_null($cart->user->email)) {
                        return 'You don\'t have an email address, please provide a valid email address and call the update email tool.';
                    }

                    $order = DB::transaction(function () use ($cart, $user) {
                        $createOrderData = CreateOrderData::from([
                            'user' => $user,
                            'cart' => $cart,
                            'status' => OrderStatus::PENDING,
                            'cartItemIds' => $cart->items->pluck('id'),
                        ]);

                        return app(OrderService::class)->createOrder($createOrderData);
                    });

                    $message = "*✅ Order Created Successfully*\n\n"
                        ."*Total Amount: ₦* $order->total_amount\n\n"
                        ."*Pay to*\n"
                        ."Account number: $order->account_number\n"
                        ."Account Name: $order->account_name\n"
                        ."Bank Name: $order->bank_name\n\n"
                        .'Thank you for your purchase!';

                    return $message;
                } catch (\Exception $e) {
                    Log::error('Error creating order: '.$e->getMessage());

                    return 'Error creating order';
                }
            });
    }
}
