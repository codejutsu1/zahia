<?php

namespace App\Prism\Tools\DeliveryAddress;

use App\Models\DeliveryAddress;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Tool;

class ListDeliveryAddressTool
{
    public static function make(User $user): Tool
    {
        return (new Tool)
            ->as('list_delivery_addresses')
            ->for('List all delivery addresses for a user.')
            ->withObjectParameter(
                'deliveryAddresses',
                'The delivery addresses parameters',
                [
                    //
                ],
            )
            ->using(function (array $deliveryAddresses) use ($user) {

                try {
                    $deliveryAddresses = DeliveryAddress::whereBelongsTo($user)->get();

                    if ($deliveryAddresses->isEmpty()) {
                        return 'No delivery addresses found';
                    }

                    $message = '';

                    foreach ($deliveryAddresses as $deliveryAddress) {
                        $mainDeliveryAddress = $deliveryAddress->is_main ? 'True' : 'False';
                        /** @phpstan-ignore-next-line */
                        $locationValue = $deliveryAddress->location->value;

                        $message .= "Building Name: {$deliveryAddress->building_name}\n";
                        $message .= "Room Number: {$deliveryAddress->room_number}\n";
                        $message .= "Floor Number: {$deliveryAddress->floor_number}\n";
                        $message .= "Location: {$locationValue}\n";
                        $message .= "Is Main: {$mainDeliveryAddress}\n";
                        $message .= "\n";
                    }

                    return $message;
                } catch (\Throwable $th) {
                    Log::error('Error listing delivery addresses: '.$th->getMessage());

                    return 'Error listing delivery addresses';
                }
            });
    }
}
