<?php

namespace App\Models;

use App\Enums\VendorStatus;
use App\Traits\HasUuidColumn;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    /** @use HasFactory<\Database\Factories\VendorFactory> */
    use HasFactory;

    use HasUuidColumn;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'website',
        'description',
        'status',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => VendorStatus::class,
        ];
    }
}
