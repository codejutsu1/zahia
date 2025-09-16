<?php

namespace App\Services\Vendor\Data;

use App\Enums\VendorStatus;
use Spatie\LaravelData\Data;

class UpdateVendorData extends Data
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public readonly int $user_id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $phone,
        public readonly string $address,
        public readonly ?string $website = null,
        public readonly ?string $description = null,
        public readonly VendorStatus $status = VendorStatus::ACTIVE,
    ) {}
}
