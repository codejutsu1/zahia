<?php

namespace App\Enums;

enum ProductType: string
{
    case FOOD = 'food';
    case LAUNDRY = 'laundry';
    case PHARMACY = 'pharmacy';
    case OTHER = 'other';

    public static function options(): array
    {
        return array_map(fn ($case) => ['label' => $case->name, 'value' => $case->value], self::cases());
    }

    public static function toArray(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
