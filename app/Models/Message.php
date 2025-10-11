<?php

namespace App\Models;

use App\Enums\MessageChannel;
use App\Enums\MessageDirection;
use App\Enums\MessageParticipant;
use App\Enums\MessageProvider;
use App\Enums\MessageStatus;
use App\Traits\HasUuidColumn;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    /** @use HasFactory<\Database\Factories\MessageFactory> */
    use HasFactory;

    use HasUuidColumn;

    protected $fillable = [
        'uuid',
        'user_id',
        'vendor_id',
        'conversation_id',
        'message_sid',
        'message',
        'direction',
        'phone',
        'profile_name',
        'channel',
        'provider',
        'participant',
        'status',
        'is_processed',
        'timestamp',
    ];

    protected function casts(): array
    {
        return [
            'channel' => MessageChannel::class,
            'provider' => MessageProvider::class,
            'status' => MessageStatus::class,
            'direction' => MessageDirection::class,
            'participant' => MessageParticipant::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
