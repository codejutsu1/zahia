<x-mail::message>
Hi [Name],

Your order (#12345) just arrived!

Quick question: How'd everything turn out?

<!-- ⭐⭐⭐⭐⭐ -->
<x-mail::button :url="''">
Leave a Quick Review
</x-mail::button>

Your feedback helps us improve (and helps others shop with confidence).

Need anything? We're always here to help.

Thanks,<br>
{{ config('app.name') }}

P.S. Not happy? Let us make it right: [Contact Us]
</x-mail::message>
