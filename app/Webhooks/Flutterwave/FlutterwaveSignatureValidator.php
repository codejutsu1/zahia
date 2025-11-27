<?php

namespace App\Webhooks\Flutterwave;

use Illuminate\Http\Request;
use InvalidArgumentException;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;
use Spatie\WebhookClient\WebhookConfig;

class FlutterwaveSignatureValidator implements SignatureValidator
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        $signature = $config->signatureHeaderName;

        if (empty($signature)) {
            throw new InvalidArgumentException('Header signature not set!');
        }

        $secret_hash = $config->signingSecret;

        if (empty($secret_hash)) {
            throw new InvalidArgumentException('Signing secret not set!');
        }

        if (is_null($request->header($signature))) {
            return true;
        }

        return $request->header($signature) === $secret_hash;
    }
}
