<?php

use App\Jobs\Webhook\ProcessFlutterwaveWebhookJob;
use App\Jobs\Webhooks\ProcessTwilioWebhookJob;
use App\Webhooks\Twilio\TwilioSignatureValidator;

return [
    'configs' => [
        [
            'name' => 'twilio',
            'signing_secret' => env('TWILIO_AUTH_TOKEN'),
            'signature_header_name' => 'x-twilio-signature',
            'signature_validator' => TwilioSignatureValidator::class,
            'webhook_profile' => \Spatie\WebhookClient\WebhookProfile\ProcessEverythingWebhookProfile::class,
            'webhook_response' => \Spatie\WebhookClient\WebhookResponse\DefaultRespondsTo::class,
            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,
            'store_headers' => [
                '*',
            ],
            'process_webhook_job' => ProcessTwilioWebhookJob::class,
        ],
        [
            'name' => 'flutterwave',
            'signing_secret' => env('FLW_SECRET_HASH'),
            'signature_header_name' => 'verif-hash',
            'signature_validator' => \App\Webhooks\Flutterwave\FlutterwaveSignatureValidator::class,
            'webhook_profile' => \Spatie\WebhookClient\WebhookProfile\ProcessEverythingWebhookProfile::class,
            'webhook_response' => \Spatie\WebhookClient\WebhookResponse\DefaultRespondsTo::class,
            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,
            'store_headers' => '*',
            'process_webhook_job' => ProcessFlutterwaveWebhookJob::class,
        ],
    ],

    /*
     * The integer amount of days after which models should be deleted.
     *
     * It deletes all records after 30 days. Set to null if no models should be deleted.
     */
    'delete_after_days' => 30,

    /*
     * Should a unique token be added to the route name
     */
    'add_unique_token_to_route_name' => false,
];
