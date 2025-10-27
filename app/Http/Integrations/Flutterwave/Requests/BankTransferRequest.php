<?php

namespace App\Http\Integrations\Flutterwave\Requests;

use App\Services\Transaction\Data\PaymentData;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasFormBody;
use Saloon\Traits\Body\HasJsonBody;
use Saloon\Traits\Plugins\AcceptsJson;

class BankTransferRequest extends Request implements HasBody
{
    use AcceptsJson;
    use HasFormBody;
    use HasJsonBody;

    public function __construct(protected PaymentData $data) {}

    /**
     * The HTTP method of the request
     */
    protected Method $method = Method::POST;

    /**
     * The endpoint for the request
     */
    public function resolveEndpoint(): string
    {
        return '/charges?type=bank_transfer';
    }

    public function defaultBody(): array
    {
        return [
            'tx_ref' => $this->data->reference,
            'amount' => $this->data->amount,
            'currency' => $this->data->currency,
            'meta' => $this->data->meta,
            'email' => $this->data->email,
        ];
    }
}
