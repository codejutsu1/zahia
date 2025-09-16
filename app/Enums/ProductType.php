<?php

namespace App\Enums;

enum ProductType: string
{
    case FOOD = 'food';
    case LAUNDRY = 'laundry';
    case PHARMACY = 'pharmacy';
    case OTHER = 'other';
}
