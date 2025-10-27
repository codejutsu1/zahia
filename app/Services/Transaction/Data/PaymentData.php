<?php

namespace App\Services\Transaction\Data;

use Spatie\LaravelData\Data;

class PaymentData extends Data
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public readonly string $reference,
        public readonly string $email,
        public readonly string $amount,
        public readonly string $currency,
        public readonly string $redirect_url = '',
        public readonly string $payment_method = '',
        public readonly array $meta = [],
        public readonly ?array $channels = ['bank_transfer'],
    ) {}
}
