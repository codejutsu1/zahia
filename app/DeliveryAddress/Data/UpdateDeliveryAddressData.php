<?php

namespace App\DeliveryAddress\Data;

use App\Enums\DeliveryAddressLocation;
use Spatie\LaravelData\Data;

class UpdateDeliveryAddressData extends Data
{
    public function __construct(
        public readonly string $building_name,
        public readonly string $room_number,
        public readonly string $floor_number,
        public readonly DeliveryAddressLocation $location,
        public readonly bool $is_main,
    ) {}
}
