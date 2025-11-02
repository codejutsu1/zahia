<?php

namespace App\Jobs\Webhooks;

use App\Enums\MessageChannel;
use App\Enums\MessageDirection;
use App\Enums\MessageParticipant;
use App\Enums\MessageProvider;
use App\Enums\MessageStatus;
use App\Enums\MessageType;
use App\Jobs\Message\DownloadAudioJob;
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

            match (data_get($payload, 'MessageType')) {
                MessageType::Text->value => $this->processTextMessage($payload, $conversation, $user),
                MessageType::Audio->value => $this->processAudioMessage($payload, $conversation, $user),
                default => throw new \Exception('Invalid message type'),
            };

            // $message = Message::create([
            //     'conversation_id' => $conversation ? $conversation->id : $conversation,
            //     'user_id' => $user ? $user->id : null,
            //     'message_sid' => data_get($payload, 'SmsMessageSid'),
            //     'message' => data_get($payload, 'Body'),
            //     'provider' => MessageProvider::TWILIO,
            //     'channel' => MessageChannel::WHATSAPP,
            //     'direction' => MessageDirection::INCOMING,
            //     'status' => MessageStatus::tryFrom(data_get($payload, 'SmsStatus'))
            //                     ?? MessageStatus::Received,
            //     'phone' => $waPhone,
            //     'profile_name' => data_get($payload, 'ProfileName'),
            //     'participant' => MessageParticipant::USER,
            //     'timestamp' => now(),
            // ]);
        } catch (\Throwable $th) {
            report($th);
            throw $th;
        }
    }

    protected function processTextMessage(array $payload, Conversation $conversation, ?User $user): void
    {
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user?->id,
            'message_sid' => data_get($payload, 'SmsMessageSid'),
            'message' => data_get($payload, 'Body'),
            'type' => MessageType::Text,
            'provider' => MessageProvider::TWILIO,
            'channel' => MessageChannel::WHATSAPP,
            'direction' => MessageDirection::INCOMING,
            'status' => MessageStatus::tryFrom(data_get($payload, 'SmsStatus'))
                            ?? MessageStatus::Received,
            'phone' => '+'.data_get($payload, 'WaId'),
            'profile_name' => data_get($payload, 'ProfileName'),
            'participant' => MessageParticipant::USER,
            'timestamp' => now(),
        ]);

        ProcessIncomingMessageJob::dispatch($message->id);
    }

    protected function processAudioMessage(array $payload, Conversation $conversation, ?User $user): void
    {
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user?->id,
            'message_sid' => data_get($payload, 'SmsMessageSid'),
            'media_url' => data_get($payload, 'MediaUrl0'),
            'type' => MessageType::Audio,
            'provider' => MessageProvider::TWILIO,
            'channel' => MessageChannel::WHATSAPP,
            'direction' => MessageDirection::INCOMING,
            'status' => MessageStatus::tryFrom(data_get($payload, 'SmsStatus'))
                            ?? MessageStatus::Received,
            'phone' => '+'.data_get($payload, 'WaId'),
            'profile_name' => data_get($payload, 'ProfileName'),
            'participant' => MessageParticipant::USER,
            'timestamp' => now(),
        ]);

        DownloadAudioJob::dispatch($message->id);
    }
}
