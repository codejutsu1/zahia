<?php

namespace App\Services\Wallet;

use App\Enums\TransactionFlow;
use App\Exceptions\WalletException;
use App\Models\Wallet;

class WalletService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function deposit(Wallet $wallet, int $amount): void
    {
        if ($amount < 0) {
            throw WalletException::invalidAmount();
        }

        $wallet->increment('balance', $amount);

        $this->createTransactionRecord(
            wallet: $wallet,
            amount: $amount,
            flow: TransactionFlow::Credit,
        );
    }

    public function purchase(Wallet $wallet, int $amount): void
    {
        if ($wallet->balance < $amount) {
            throw WalletException::insufficientBalance();
        }

        if ($amount < 0) {
            throw WalletException::invalidAmount();
        }

        $wallet->decrement('balance', $amount);

        $this->createTransactionRecord(
            wallet: $wallet,
            amount: $amount,
            flow: TransactionFlow::Debit,
        );
    }

    protected function createTransactionRecord(
        Wallet $wallet,
        int $amount,
        TransactionFlow $flow
    ): void {
        $wallet->transactions()->create([
            'amount' => $amount,
            'flow' => $flow,
        ]);
    }
}
