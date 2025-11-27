<?php

namespace App\Jobs\Webhook;

use App\Exceptions\PaymentException;
use App\Services\Transaction\TransactionService;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;

class ProcessFlutterwaveWebhookJob extends ProcessWebhookJob
{
    public function handle(): void
    {
        $payload = $this->webhookCall->payload;

        try {
            $this->handleWebhook($payload);
        } catch (\Throwable $th) {
            report($th);

            throw new PaymentException(
                provider: 'Flutterwave',
                message: 'Invalid webhook payload',
                response_data: $payload,
            );
        }
    }

    protected function handleWebhook(array $payload): void
    {
        $reference = data_get($payload, 'txRef');

        try {
            app(TransactionService::class)->verifyTransaction($reference);
        } catch (\Throwable $th) {
            report($th);

            throw new PaymentException(
                provider: 'Flutterwave',
                message: 'Failed to verify transaction on webhook!',
                response_data: $payload,
            );
        }
    }
}
