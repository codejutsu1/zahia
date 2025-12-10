<?php

namespace App\Services\Order\Actions;

use App\Enums\CartStatus;
use App\Enums\DeliveryStatus;
use App\Enums\OrderItemStatus;
use App\Enums\TransactionFlow;
use App\Enums\TransactionPaymentProvider;
use App\Enums\TransactionStatus;
use App\Enums\WalletStatus;
use App\Enums\WalletType;
use App\Exceptions\OrderException;
use App\Facade\Transaction as TransactionFacade;
use App\Jobs\Order\NotifyVendor;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Order\Data\CreateOrderData;
use App\Services\Transaction\Data\PaymentData;
use App\Services\Transaction\Data\TransactionResponse;
use Illuminate\Support\Str;

class CreateOrderAction
{
    public function execute(CreateOrderData $data): Order
    {
        $this->validateData($data);

        $cart = $this->checkForSelectedCartItems($data);

        $totalAmount = $this->getTotalAmount($cart, $data);

        $order = $this->createOrder($cart, $data, $totalAmount);

        $this->createOrderItems($cart, $order, $data);

        $this->initializeTransaction($order);

        $this->createDelivery($order, $data);

        $this->notifyVendor($order);

        return $order;
    }

    protected function validateData(CreateOrderData $data): void
    {
        if ($data->user->email === null) {
            throw new \Exception('This user does not have an email address');
        }

        if ($data->user->id !== $data->cart->user_id) {
            throw new \Exception('This user does not have this cart');
        }
        /** @phpstan-ignore-next-line */
        if ($data->cart->status !== CartStatus::ACTIVE) {
            throw new \Exception('This cart is not active');
        }
    }

    protected function checkForSelectedCartItems(CreateOrderData $data): Cart
    {
        if ($data->cartItemIds->isEmpty()) {
            return $data->cart;
        }

        $cart = Cart::create([
            'user_id' => $data->user->id,
            'vendor_id' => $data->cart->vendor_id,
            'status' => CartStatus::ACTIVE,
        ]);

        CartItem::whereIn('id', $data->cartItemIds)->update([
            'cart_id' => $cart->id,
        ]);

        return $cart;
    }

    protected function getTotalAmount(Cart $cart, CreateOrderData $data): int
    {
        $cart->load('items');

        /* @phpstan-ignore-next-line */
        return $cart->items->sum(fn (CartItem $cartItem) => $cartItem->product->price * $cartItem->quantity);
    }

    protected function createOrder(
        Cart $cart,
        CreateOrderData $data,
        int $totalAmount
    ): Order {
        return Order::create([
            'user_id' => $data->user->id,
            'cart_id' => $cart->id,
            'total_amount' => $totalAmount,
            'status' => $data->status,
        ]);
    }

    protected function createOrderItems(Cart $cart, Order $order, CreateOrderData $data): void
    {
        $cart->load('items');
        $cartItem = $cart->items;

        /* @phpstan-ignore-next-line */
        $orderItemData = $cartItem->map(function (CartItem $cartItem) use ($order) {
            return [
                'uuid' => Str::uuid(),
                'order_id' => $order->id,
                'product_id' => $cartItem->product_id,
                'quantity' => $cartItem->quantity,
                /* @phpstan-ignore-next-line */
                'price' => $cartItem->product->price,
                'status' => OrderItemStatus::AVAILABLE,
            ];
        })->toArray();

        OrderItem::insert($orderItemData);

        $cart->update([
            'status' => CartStatus::COMPLETED,
        ]);
    }

    protected function initializeTransaction(Order $order): void
    {
        if (app()->environment('testing')) {
            return;
        }

        /**@phpstan-ignore-next-line */
        if (is_null($order->user->email)) {
            throw OrderException::nullableEmail();
        }

        $reference = Str::uuid();

        $provider = TransactionPaymentProvider::from(config('services.payment_provider'));

        $payload = PaymentData::from([
            'reference' => $reference,
            'email' => $order->user->email,
            'amount' => $order->total_amount,
            'currency' => 'NGN',
            'redirect_url' => 'https://lol.com',
            'payment_method' => 'bank_transfer',
            'meta' => [
                /* @phpstan-ignore-next-line */
                'order_uuid' => $order->uuid->toString(),
            ],
        ]);

        $response = TransactionFacade::driver($provider->value)
            ->initiateTransaction($payload);

        $order->update([
            'account_name' => $response->account_name ?? 'Zahia Limited',
            'account_number' => $response->account_number,
            'bank_name' => $response->bank_name,
        ]);

        $this->createTransaction($order, $response, $provider, $reference);
    }

    protected function createTransaction(
        Order $order,
        TransactionResponse $response,
        TransactionPaymentProvider $provider,
        string $reference
    ): void {
        /* @phpstan-ignore-next-line */
        $wallet = $order->user->wallet()->firstOrCreate(
            /* @phpstan-ignore-next-line */
            ['user_id' => $order->user->id],
            [
                'uuid' => Str::uuid(),
                'balance' => 0,
                'status' => WalletStatus::Active,
                'type' => WalletType::User,
            ]
        );

        $order->transaction()->create([
            'wallet_id' => $wallet->id,
            'order_id' => $order->id,
            'amount' => $response->amount,
            'currency' => 'NGN',
            'payment_provider' => $provider,
            'reference' => $reference,
            'payment_method' => 'bank_transfer',
            'payment_status' => TransactionStatus::Pending,
            'status' => TransactionStatus::Pending,
            'flow' => TransactionFlow::Debit,
        ]);
    }

    protected function notifyVendor(Order $order): void
    {
        NotifyVendor::dispatch($order->id);
    }

    protected function createDelivery(Order $order, CreateOrderData $data): void
    {
        $deliveryAddress = $data->deliveryAddress
            /* @phpstan-ignore-next-line */
            ?? $order->user->deliveryAddresses()->where('is_main', true)->first()
            ?? throw new \Exception('No delivery address found');

        $order->delivery()->create([
            'delivery_address_id' => $deliveryAddress->id,
            'address' => $deliveryAddress->fullAddress(),
            'status' => DeliveryStatus::Active,
        ]);
    }
}
