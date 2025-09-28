<?php

namespace App\Http\Integrations\Twilio\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasFormBody;

class SendMessageRequest extends Request implements HasBody
{
    use HasFormBody;

    public function __construct(protected string $message) {}

    /**
     * The HTTP method of the request
     */
    protected Method $method = Method::POST;

    /**
     * The endpoint for the request
     */
    public function resolveEndpoint(): string
    {
        return '/Messages.json';
    }

    public function defaultBody(): array
    {
        return [
            'From' => 'whatsapp:'.config('services.twilio.phone'),
            'To' => 'whatsapp:+2349137836455',
            'Body' => $this->message,
        ];
    }
}
