<?php

namespace App\Prism\Tools\DeliveryAddress;

use App\Enums\DeliveryAddressLocation;
use App\Models\User;
use App\Services\DeliveryAddress\Data\CreateDeliveryAddressData;
use App\Services\DeliveryAddress\DeliveryAddressService;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Schema\BooleanSchema;
use Prism\Prism\Schema\EnumSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Tool;

class CreateDeliveryAddressTool
{
    public static function make(User $user): Tool
    {
        return (new Tool)
            ->as('create_delivery_address')
            ->for('Create a delivery address, this tool creates a delivery address.')
            ->withObjectParameter(
                'address',
                'The delivery address parameters',
                [
                    new StringSchema('building_name', 'The building name'),
                    new StringSchema('room_number', 'The room number'),
                    new StringSchema('floor_number', 'The floor number'),
                    new EnumSchema('location', 'The location', DeliveryAddressLocation::toArray()),
                    new BooleanSchema('is_main', 'Whether the delivery address is the main delivery address'),
                ],
                requiredFields: [
                    'building_name',
                    'room_number',
                    'floor_number',
                    'location',
                ]
            )
            ->using(function (array $address) use ($user) {
                $address['user_id'] = $user->id;

                try {
                    $deliveryAddressData = CreateDeliveryAddressData::from($address);

                    app(DeliveryAddressService::class)->createDeliveryAddress($deliveryAddressData);

                    return 'Delivery address created successfully. do you want to continue?';
                } catch (\Throwable $th) {
                    Log::error('Error creating delivery address: '.$th->getMessage());

                    return 'Error creating delivery address';
                }
            });
    }
}
