<x-mail::message>
Hi <b>{{ $order->user->name }}</b> 👋,

Your order is shipped and headed your way! 🎉

Order: <b>#{{ $order->order_id }}</b>

Carrier: [CARRIER_NAME]

Tracking: [TRACKING_NUMBER]

Arriving: [DELIVERY_DATE]

Shipping to:
[FULL_ADDRESS]

<x-mail::button :url="''">
Track your Package
</x-mail::button>

Questions? We're here to help.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
