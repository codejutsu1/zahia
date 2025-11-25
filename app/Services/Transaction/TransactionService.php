<?php

namespace App\Services\Transaction;

use App\Enums\TransactionStatus;
use App\Exceptions\OrderException;
use App\Exceptions\TransactionException;
use App\Facade\Transaction as FacadeTransaction;
use App\Models\Order;
use App\Models\Transaction;
use App\Services\Transaction\Data\TransactionData;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function verifyTransaction(string $reference): TransactionStatus
    {
        $transaction = Transaction::where('reference', $reference)->first();

        if (! $transaction) {
            throw TransactionException::notFound();
        }

        if ($transaction->finalStatus()) {
            /* @phpstan-ignore-next-line */
            return $transaction->status;
        }

        /* @phpstan-ignore-next-line */
        $data = FacadeTransaction::driver($transaction->payment_provider->value)
            ->verifyTransaction($transaction->reference);

        $newStatus = match ($data->status) {
            TransactionStatus::Processed => TransactionStatus::Processed,
            TransactionStatus::Failed => TransactionStatus::Failed,
            default => TransactionStatus::Pending,
        };

        DB::transaction(function () use ($transaction, $data, $newStatus) {
            $row = Transaction::where('reference', $transaction->reference)
                ->lockForUpdate()
                ->first();

            if ($row->finalStatus()) {
                return;
            }

            $this->updateTransaction(
                transaction: $row,
                data: $data,
                transactionStatus: $newStatus
            );
        });

        /* @phpstan-ignore-next-line */
        return Transaction::firstWhere('reference', $reference)
            ->status;
    }

    protected function updateTransaction(
        Transaction $transaction,
        TransactionData $data,
        TransactionStatus $transactionStatus
    ): Transaction {
        if ($transaction->finalStatus()) {
            return $transaction;
        }

        /* @phpstan-ignore-next-line */
        $transaction->status = $transactionStatus;
        /* @phpstan-ignore-next-line */
        $transaction->payload = $data->toArray();

        match ($transactionStatus) {
            TransactionStatus::Processed => $transaction->completed_at = now(),
            TransactionStatus::Failed => $transaction->failed_at = now(),
            default => null,
        };

        $transaction->save();

        $this->purchaseOrder(data: $data, transaction: $transaction);

        return $transaction;
    }

    public function purchaseOrder(TransactionData $data, Transaction $transaction): void
    {
        if ($transaction->isProcessed()) {
            $orderUuid = data_get($data, 'meta.order_uuid');

            $order = Order::with('user.wallet')
                ->where('uuid', $orderUuid)
                ->first();

            if (! $order) {
                throw OrderException::notFound();
            }

            $walletService = app(WalletService::class);

            $walletService->deposit(
                /* @phpstan-ignore-next-line */
                wallet: $order->user->wallet,
                amount: $data->amount,
            );

            // ProcessOrderJob::dispatch($order)->afterCommit();
        }
    }
}
