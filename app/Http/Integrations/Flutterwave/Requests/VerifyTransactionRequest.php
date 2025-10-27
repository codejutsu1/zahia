<?php

namespace App\Http\Integrations\Flutterwave\Requests;

use App\Enums\TransactionStatus;
use App\Services\Transaction\Data\TransactionData;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class VerifyTransactionRequest extends Request
{
    public function __construct(protected string $transactionReference) {}

    /**
     * The HTTP method of the request
     */
    protected Method $method = Method::GET;

    /**
     * The endpoint for the request
     */
    public function resolveEndpoint(): string
    {
        return "/transactions/verify_by_reference?tx_ref={$this->transactionReference}";
    }

    public function createDtoFromResponse(Response $response): TransactionData
    {
        $data = $response->json('data');

        return new TransactionData(
            email: data_get($data, 'customer.email'),
            reference: data_get($data, 'tx_ref'),
            ext_reference: data_get($data, 'flw_ref'),
            amount: data_get($data, 'amount'),
            currency: data_get($data, 'currency'),
            status: $this->computeStatus(data_get($data, 'status')),
            payment_type: data_get($data, 'payment_type'),
            meta: [
                'order_uid' => data_get($data, 'meta.order_uid'),
            ],
            authorization: data_get($data, 'authorization'),
            customer: data_get($data, 'customer'),
        );
    }

    protected function computeStatus(string $status): string
    {
        $status = match ($status) {
            'successful' => TransactionStatus::Processed,
            'failed' => TransactionStatus::Failed,
            'pending' => TransactionStatus::Pending,
            default => TransactionStatus::Pending
        };

        return $status->value;
    }
}
