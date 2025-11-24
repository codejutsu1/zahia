<x-mail::message>
Hi <b>{{ $order->user->name }}</b> 👋,

Thanks for your order! 

It's confirmed and we're packing it up now.

OrderID: <b>#{{ $order->order_id }}</b>

Total: <b>₦{{ number_format($order->total_amount, 2) }}</b>

Shipping to:
[Address]

What's next
→ Track your order: 

<x-mail::button :url="''">
View Order Details
</x-mail::button>

Do you have any questions? Reply to this email and we'll get back to you as soon as possible.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
