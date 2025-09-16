<?php

namespace App\Services\Vendor;

use App\Models\Vendor;
use App\Services\Vendor\Data\CreateVendorData;
use App\Services\Vendor\Data\UpdateVendorData;

class VendorService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function createVendor(CreateVendorData $data): Vendor
    {
        return Vendor::create($data->toArray());
    }

    public function updateVendor(Vendor $vendor, UpdateVendorData $data): Vendor
    {
        $vendor->update($data->toArray());

        return $vendor;
    }
}
