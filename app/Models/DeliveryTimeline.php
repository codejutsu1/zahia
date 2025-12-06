<?php

namespace App\Models;

use App\Enums\DeliveryTimelineStatus;
use App\Enums\DeliveryTimelineType;
use App\Traits\HasUuidColumn;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryTimeline extends Model
{
    /** @use HasFactory<\Database\Factories\DeliveryTimelineFactory> */
    use HasFactory;

    use HasUuidColumn;

    protected $fillable = [
        'delivery_id',
        'title',
        'subtitle',
        'status',
        'type',
        'completed_at',
        'delayed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => DeliveryTimelineStatus::class,
            'type' => DeliveryTimelineType::class,
        ];
    }
}
