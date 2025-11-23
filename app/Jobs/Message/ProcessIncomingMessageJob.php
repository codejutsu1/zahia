<?php

namespace App\Jobs\Message;

use App\Enums\MessageChannel;
use App\Enums\MessageDirection;
use App\Enums\MessageParticipant;
use App\Enums\MessageProvider;
use App\Enums\MessageStatus;
use App\Facade\Chatbot;
use App\Facade\Llm;
use App\Models\Message;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;

class ProcessIncomingMessageJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $messageId)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $message = Message::find($this->messageId);

        if (! $message) {
            Log::info('Message not found');

            return;
        }

        $conversation = $message->conversation;

        $user = User::find($message->user_id);

        if (! $user) {
            // return Chatbot::sendMessage('You do not exist in our system, please contact your branch manager to be added to the system.');
            Log::info('You do not exist in our system, please contact your branch manager to be added to the system.');
        }

        $messages = $conversation
                        /** @phpstan-ignore-next-line */
                        ? $conversation->messages()->orderBy('created_at')->get()
                        : collect();

        $prismMessages = $messages->map(function ($message) {
            return $message->participant == MessageParticipant::USER
                ? new UserMessage($message->message)
                : new AssistantMessage($message->message);
        })->all();

        // $responseText = Llm::prompt($prismMessages, $user);
        // $responseText = Llm::vendorPrompt($prismMessages, $user);

        // $conversation->messages()->create([
        //     'user_id' => $message->user_id,
        //     'message' => $responseText,
        //     'status' => MessageStatus::Received,
        //     'direction' => MessageDirection::INCOMING,
        //     'channel' => MessageChannel::WHATSAPP,
        //     'provider' => MessageProvider::TWILIO,
        //     'participant' => MessageParticipant::ASSISTANT,
        //     'timestamp' => now(),
        //     'is_processed' => true,
        // ]);

        $message->update([
            'is_processed' => true,
        ]);

        // Chatbot::sendMessage($responseText);
        // Log::info($responseText);
    }
}
