<?php

namespace App\Models;

use App\Enums\DeliveryAddressLocation;
use App\Enums\DeliveryAddressStatus;
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
        'building_name',
        'location',
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

    protected function casts(): array
    {
        return [
            'status' => DeliveryAddressStatus::class,
            'location' => DeliveryAddressLocation::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
