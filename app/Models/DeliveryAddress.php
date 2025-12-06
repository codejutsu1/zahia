<?php

namespace App\Models;

use App\Traits\HasUuidColumn;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryAddress extends Model
{
    /** @use HasFactory<\Database\Factories\DeliveryAddressFactory> */
    use HasFactory;

    use HasUuidColumn;

    protected $fillable = [
        'user_id',
        'room_number',
        'floor_number',
        'building_number',
        'street_name',
        'city',
        'state',
        'country',
        'postal_code',
        'description',
        'landmark',
        'instructions',
        'phone_number',
        'is_storey',
        'is_estate',
        'is_main',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
