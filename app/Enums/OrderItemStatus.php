<?php

namespace App\Enums;

enum OrderItemStatus: string
{
    case AVAILABLE = 'available';
    case UNAVAILABLE = 'unavailable';
    case OUT_OF_STOCK = 'out_of_stock';
}
