<?php

namespace App\Contracts;

use App\Services\Transaction\Data\PaymentData;
use App\Services\Transaction\Data\TransactionData;
use App\Services\Transaction\Data\TransactionResponse;

interface InteractWithTransaction
{
    public function initiateTransaction(PaymentData $data): TransactionResponse;

    public function verifyTransaction(string $reference): TransactionData;
}
