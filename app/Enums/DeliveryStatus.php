<?php

namespace App\Enums;

enum DeliveryStatus: string
{
    case Active = 'active';

    case Completed = 'completed';
}
