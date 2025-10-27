<?php

namespace App\Services\Transaction\Data;

use Spatie\LaravelData\Data;

class TransactionResponse extends Data
{
    public function __construct(
        public readonly string $account_number,
        public readonly string $bank_name,
        public readonly string $expires_at,
        public readonly string $reference,
        public readonly string $amount,
    ) {}
}
