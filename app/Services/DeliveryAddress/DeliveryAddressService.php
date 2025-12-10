<?php

namespace App\Services\DeliveryAddress;

use App\DeliveryAddress\Data\UpdateDeliveryAddressData;
use App\Models\DeliveryAddress;
use App\Models\User;
use App\Services\DeliveryAddress\Data\CreateDeliveryAddressData;

class DeliveryAddressService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function createDeliveryAddress(CreateDeliveryAddressData $data): DeliveryAddress
    {
        if ($data->is_main) {
            $user = User::find($data->user_id);

            if (! is_null($user)) {
                $user->deliveryAddresses()->update(['is_main' => false]);
            }
        }

        $deliveryAddress = DeliveryAddress::create($data->toArray());

        return $deliveryAddress;
    }

    public function updateDeliveryAddress(DeliveryAddress $deliveryAddress, UpdateDeliveryAddressData $data): DeliveryAddress
    {
        if ($data->is_main) {
            $user = User::find($deliveryAddress->user_id);

            if (! is_null($user)) {
                $user->deliveryAddresses()->update(['is_main' => false]);
            }
        }

        $deliveryAddress->update($data->toArray());

        return $deliveryAddress;
    }
}
