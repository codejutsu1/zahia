<?php

namespace App\Enums;

enum DeliveryTimelineType: string
{
    case Confirmed = 'confirmed';

    case Preparing = 'preparing';

    case Accepted = 'accepted';

    case Enroute = 'enroute';

    case Arrived = 'arrived';

    public function getTitle(): string
    {
        return match ($this) {
            self::Confirmed => 'Your Order is confirmed',
            self::Preparing => 'Preparing your order',
            self::Accepted => 'Order Accepted by a rider',
            self::Enroute => 'Order is en-route to your location',
            self::Arrived => 'Order has arrived - Chops Time!!!',
        };
    }

    public function getSubtitle(): string
    {
        return match ($this) {
            self::Confirmed => 'The kitchen has verified your order',
            self::Preparing => 'Est. to take about 40 - 120 minutes',
            self::Accepted => 'Your order is waiting to be picked up',
            self::Enroute => 'Your order is on it\'s way to you',
            self::Arrived => 'The rider is at your location with your order',
        };
    }

    public static function allTimelines(): array
    {
        return [
            self::Confirmed,
            self::Preparing,
            self::Accepted,
            self::Enroute,
            self::Arrived,
        ];
    }
}
