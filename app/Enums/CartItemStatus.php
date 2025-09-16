<?php

namespace App\Enums;

enum CartItemStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}
