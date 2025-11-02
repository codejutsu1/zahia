<?php

namespace App\Jobs\Message;

use App\Enums\AudioFormat;
use App\Models\Message;
use App\Services\Audio\AudioService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DownloadAudioJob implements ShouldQueue
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

        $accountSid = config('services.twilio.account_sid');
        $authToken = config('services.twilio.auth_token');

        $audioUrl = $message->media_url;

        $filename = 'voice_'.$message->message_sid.'.ogg';
        $path = 'audio/'.$filename;

        $directory = 'audio';

        if (! Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }

        Http::withBasicAuth($accountSid, $authToken)
            ->withOptions(['sink' => Storage::disk('public')->path($path)])
            ->get($audioUrl);

        if (! Storage::disk('public')->exists($path)) {
            Log::error('Audio download failed', ['url' => $audioUrl]);

            return;
        }

        $convertedPath = app(AudioService::class)->convertAudio($path, AudioFormat::MP3);

        $message->update([
            'path' => $convertedPath,
        ]);

        ProcessIncomingAudioJob::dispatch($message->id);

        Log::info('Audio saved successfully', ['path' => $path]);
    }
}
