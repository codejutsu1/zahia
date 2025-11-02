<?php

namespace App\Webhooks\Twilio;

use Illuminate\Http\Request;
use Spatie\WebhookClient\Exceptions\InvalidConfig;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;
use Spatie\WebhookClient\WebhookConfig;
use Twilio\Security\RequestValidator;

class TwilioSignatureValidator implements SignatureValidator
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        $signature = $request->header($config->signatureHeaderName);

        if (! $signature) {
            return false;
        }

        $secret = $config->signingSecret;

        if (empty($secret)) {
            throw InvalidConfig::signingSecretNotSet();
        }

        $validator = new RequestValidator($secret);

        $url = 'https://abypqavaui.sharedwithexpose.com/webhooks/incoming-message';

        // $url = $request->fullUrl();

        $requestData = $request->post();

        $isValid = $validator->validate($signature, $url, $requestData);

        return $isValid;
    }
}
