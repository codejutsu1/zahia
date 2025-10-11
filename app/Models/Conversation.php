<?php

namespace App\Models;

use App\Traits\HasUuidColumn;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    /** @use HasFactory<\Database\Factories\ConversationFactory> */
    use HasFactory;

    use HasUuidColumn;

    protected $fillable = [
        'uuid',
        'user_id',
        'vendor_id',
    ];
}
