<?php

namespace App\Enums;

enum DeliveryAddressStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
