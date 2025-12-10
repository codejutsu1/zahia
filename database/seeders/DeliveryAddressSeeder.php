<?php

namespace Database\Seeders;

use App\Models\DeliveryAddress;
use App\Models\User;
use Illuminate\Database\Seeder;

class DeliveryAddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DeliveryAddress::factory()->create([
            'user_id' => User::first()->id,
            'is_main' => true,
        ]);
    }
}
