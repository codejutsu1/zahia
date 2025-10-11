<?php

namespace App\Jobs\Webhooks;

use App\Enums\MessageChannel;
use App\Enums\MessageDirection;
use App\Enums\MessageParticipant;
use App\Enums\MessageProvider;
use App\Enums\MessageStatus;
use App\Jobs\Message\ProcessIncomingMessageJob;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Queue\Queueable;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;

class ProcessTwilioWebhookJob extends ProcessWebhookJob
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $payload = $this->webhookCall->payload;

        try {
            $waPhone = '+'.data_get($payload, 'WaId');

            $user = User::firstWhere('phone', $waPhone);

            $conversation = null;

            if ($user) {
                $lastConversation = Conversation::where('user_id', $user->id)
                    ->latest()
                    ->first();

                $shouldStartNew = ! $lastConversation || $lastConversation->updated_at->diffInMinutes(now()) > 30;

                $conversation = $shouldStartNew
                                    ? Conversation::create(['user_id' => $user->id])
                                    : $lastConversation;
            }

            $message = Message::create([
                'conversation_id' => $conversation ? $conversation->id : $conversation,
                'user_id' => $user ? $user->id : null,
                'message_sid' => data_get($payload, 'SmsMessageSid'),
                'message' => data_get($payload, 'Body'),
                'provider' => MessageProvider::TWILIO,
                'channel' => MessageChannel::WHATSAPP,
                'direction' => MessageDirection::INCOMING,
                'status' => MessageStatus::tryFrom(data_get($payload, 'SmsStatus'))
                                ?? MessageStatus::Received,
                'phone' => $waPhone,
                'profile_name' => data_get($payload, 'ProfileName'),
                'participant' => MessageParticipant::USER,
                'timestamp' => now(),
            ]);

            ProcessIncomingMessageJob::dispatch($message);
        } catch (\Throwable $th) {
            report($th);
            throw $th;
        }
    }
}
