<?php

namespace App\Jobs\Message;

use App\Facade\Llm;
use App\Models\Message;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessIncomingAudioJob implements ShouldQueue
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

        $user = User::find($message->user_id);

        if (! $user) {
            // return Chatbot::sendMessage('You do not exist in our system, please contact your branch manager to be added to the system.');
            Log::info('You do not exist in our system, please contact your branch manager to be added to the system.');

            return;
        }

        $responseText = Llm::driver('openai')->audio($message->path);

        $message->update([
            'message' => $responseText,
        ]);

        ProcessIncomingMessageJob::dispatch($message->id);

    }
}
