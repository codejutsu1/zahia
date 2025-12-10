<?php

namespace Database\Factories;

use App\Enums\DeliveryAddressLocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DeliveryAddress>
 */
class DeliveryAddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'room_number' => $this->faker->buildingNumber(),
            'floor_number' => $this->faker->numberBetween(1, 10),
            'building_number' => $this->faker->buildingNumber(),
            'building_name' => $this->faker->company(),
            'location' => $this->faker->randomElement(DeliveryAddressLocation::values()),
            'street_name' => $this->faker->streetName(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
        ];
    }
}
