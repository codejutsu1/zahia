<?php

namespace App\Http\Integrations\Twilio;

use Saloon\Http\Auth\BasicAuthenticator;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;

class TwilioConnector extends Connector
{
    use AcceptsJson;

    /**
     * The Base URL of the API
     */
    public function resolveBaseUrl(): string
    {
        return 'https://api.twilio.com/2010-04-01/Accounts/'.config('services.twilio.account_sid');
    }

    /**
     * Default headers for every request
     */
    protected function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Default HTTP client options
     */
    protected function defaultAuth(): BasicAuthenticator
    {
        return new BasicAuthenticator(
            username: config('services.twilio.account_sid'),
            password: config('services.twilio.auth_token'),
        );
    }
}
