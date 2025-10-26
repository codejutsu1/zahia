<?php

namespace App\Enums;

enum ProductStatus: string
{
    case ACTIVE = 'active';
    case OUT_OF_STOCK = 'out_of_stock';
    case UNAVAILABLE = 'unavailable';

    public static function options(): array
    {
        return array_map(fn ($case) => ['label' => $case->name, 'value' => $case->value], self::cases());
    }

    public static function toArray(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
