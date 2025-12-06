<?php

namespace App\Enums;

enum DeliveryTimelineStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Delayed = 'delayed';
    case Active = 'active';
}
