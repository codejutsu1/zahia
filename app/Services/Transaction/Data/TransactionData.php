<?php

namespace App\Services\Transaction\Data;

use Spatie\LaravelData\Data;

class TransactionData extends Data
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public readonly string $reference,
        public readonly string $email,
        public readonly int $amount,
        public readonly string $currency,
        public readonly string $status,
        public readonly array|int $meta,
        public readonly array $customer,
        public readonly ?string $payment_type = null,
        public readonly ?string $ext_reference = null,
        public readonly ?array $authorization = [],
    ) {}
}
