<?php

use App\Enums\MessageChannel;
use App\Enums\MessageDirection;
use App\Enums\MessageParticipant;
use App\Enums\MessageProvider;
use App\Enums\MessageStatus;
use App\Http\Controllers\Api\Transaction\VerifyTransactionController;
use App\Jobs\Message\ProcessIncomingAudioJob;
use App\Jobs\Message\ProcessIncomingMessageJob;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/transaction/verify', VerifyTransactionController::class);

Route::post('/ai', function (Request $request) {
    $request->validate([
        'body' => ['required', 'string'],
    ]);

    $phone = '+2349137836455';

    $user = User::find(1);
    $user->update(['phone' => $phone]);
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
        'user_id' => $user->id,
        'message' => $request->body,
        'provider' => MessageProvider::TWILIO,
        'channel' => MessageChannel::WHATSAPP,
        'direction' => MessageDirection::INCOMING,
        'status' => MessageStatus::Received,
        'participant' => MessageParticipant::USER,
        'phone' => $phone,
        'timestamp' => now(),
    ]);

    ProcessIncomingMessageJob::dispatch($message->id);

    return response()->json('sent', 200);
});

Route::post('/audio', function (Request $request) {
    $request->validate([
        'audio' => ['required', 'file', 'mimes:mp3,wav,ogg,webm'],
    ]);

    $phone = '+2349137836455';

    $user = User::find(1);
    $user->update(['phone' => $phone]);
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

    $audio = $request->file('audio');

    $filename = $user->id.'-'.time().'.'.$audio->getClientOriginalExtension();

    $path = $audio->storeAs('audio', $filename, 'public');

    $message = Message::create([
        'conversation_id' => $conversation ? $conversation->id : $conversation,
        'user_id' => $user->id,
        'path' => $path,
        'provider' => MessageProvider::TWILIO,
        'channel' => MessageChannel::WHATSAPP,
        'direction' => MessageDirection::INCOMING,
        'status' => MessageStatus::Received,
        'participant' => MessageParticipant::USER,
        'phone' => $phone,
        'timestamp' => now(),
    ]);

    ProcessIncomingAudioJob::dispatch($message->id);

    return response()->json('sent', 200);
});
