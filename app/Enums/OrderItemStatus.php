<?php

namespace App\Enums;

enum OrderItemStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case CANCELLED = 'cancelled';
}
