<?php

namespace App\Enums;

enum DeliveryAddressLocation: string
{
    case Eziobodo = 'eziobodo';
    case Umuchima = 'umuchima';
    case Obinze = 'obinze';

    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
