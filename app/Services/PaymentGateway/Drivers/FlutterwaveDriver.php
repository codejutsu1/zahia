<?php

namespace App\Services\PaymentGateway\Drivers;

use App\Contracts\InteractWithTransaction;
use App\Enums\TransactionPaymentProvider;
use App\Exceptions\PaymentException;
use App\Http\Integrations\Flutterwave\FlutterwaveConnector;
use App\Http\Integrations\Flutterwave\Requests\BankTransferRequest;
use App\Http\Integrations\Flutterwave\Requests\VerifyTransactionRequest;
use App\Services\Transaction\Data\PaymentData;
use App\Services\Transaction\Data\TransactionData;
use App\Services\Transaction\Data\TransactionResponse;

class FlutterwaveDriver implements InteractWithTransaction
{
    protected FlutterwaveConnector $connector;

    public function __construct()
    {
        $this->connector = new FlutterwaveConnector;
    }

    public function initiateTransaction(PaymentData $data): TransactionResponse
    {
        return match ($data->payment_method) {
            'bank_transfer' => $this->processBankTransfer($data),
            default => $this->processBankTransfer($data),
        };
    }

    protected function processBankTransfer(PaymentData $data): TransactionResponse
    {
        $request = new BankTransferRequest($data);

        $response = $this->connector->send($request);

        if ($response->failed()) {
            // throw new PaymentException(
            //     message: 'Failed to initiate transaction.',
            //     provider: TransactionPaymentProvider::Flutterwave->value,
            //     response_data: $response->json()
            // );
        }

        $responseData = $response->json();

        return TransactionResponse::from([
            'account_number' => data_get($responseData, 'meta.authorization.transfer_account'),
            'bank_name' => data_get($responseData, 'meta.authorization.transfer_bank'),
            'expires_at' => data_get($responseData, 'meta.authorization.account_expiration'),
            'reference' => data_get($responseData, 'meta.authorization.transfer_reference'),
            'amount' => data_get($responseData, 'meta.authorization.transfer_amount'),
        ]);
    }

    public function verifyTransaction(string $reference): TransactionData
    {
        $request = new VerifyTransactionRequest($reference);

        $response = $this->connector->send($request);

        if ($response->failed()) {
            // throw new PaymentException(
            //     message: 'Failed to verify transaction.',
            //     provider: TransactionPaymentProvider::Flutterwave->value,
            //     response_data: $response->json()
            // );
        }

        return $response->dto();
    }
}
