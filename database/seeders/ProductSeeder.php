<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\VendorStatus;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $blessing = User::factory()->create();
        // First Vendor
        $blessingRestaurant = Vendor::create([
            'user_id' => $blessing->id,
            'name' => 'Blessing Restaurant',
            'email' => 'vendor1@example.com',
            'phone' => '1234567890',
            'address' => '1234567890',
            'website' => 'https://example.com',
            'description' => 'Blessing Restaurant description',
            'status' => VendorStatus::ACTIVE,
        ]);

        $blessingRestaurant->products()->createMany([
            [
                'name' => 'Big Plate Fried Rice',
                'description' => 'Blessing Restaurant Product 1 description',
                'price' => 2000,
                'quantity' => 100,
                'status' => ProductStatus::ACTIVE,
                'type' => ProductType::FOOD,
                'is_addon' => true,
            ],
            [
                'name' => 'Small Plate Fried Rice',
                'description' => 'Blessing Restaurant Product 2 description',
                'price' => 1800,
                'quantity' => 200,
                'status' => ProductStatus::ACTIVE,
                'type' => ProductType::FOOD,
            ],
            [
                'name' => 'Big Plate Jollof Rice',
                'description' => 'Blessing Restaurant Product 3 description',
                'price' => 2000,
                'quantity' => 300,
                'status' => ProductStatus::ACTIVE,
                'type' => ProductType::FOOD,
            ],
            [
                'name' => 'Small Plate Jollof Rice',
                'description' => 'Blessing Restaurant Product 4 description',
                'price' => 1800,
                'quantity' => 400,
                'status' => ProductStatus::ACTIVE,
                'type' => ProductType::FOOD,
            ],
            [
                'name' => 'Fried Chicken',
                'description' => 'Blessing Restaurant Product 5 description',
                'price' => 3500,
                'quantity' => 50,
                'status' => ProductStatus::ACTIVE,
                'type' => ProductType::FOOD,
            ],
            [
                'name' => 'Plantain',
                'description' => 'Blessing Restaurant Product 6 description',
                'price' => 500,
                'quantity' => 20,
                'is_addon' => true,
                'status' => ProductStatus::ACTIVE,
                'type' => ProductType::FOOD,
            ],
            [
                'name' => 'Salad',
                'description' => 'Blessing Restaurant Product 7 description',
                'price' => 500,
                'quantity' => 700,
                'is_addon' => true,
                'status' => ProductStatus::ACTIVE,
                'type' => ProductType::FOOD,
            ],
        ]);

        $mira = User::factory()->create();

        // Second Vendor
        $miraKitchen = Vendor::create([
            'user_id' => $mira->id,
            'name' => 'Mira Kitchen',
            'email' => 'vendor2@example.com',
            'phone' => '1234567890',
            'address' => '1234567890',
            'website' => 'https://example.com',
            'description' => 'Mira Kitchen description',
            'status' => VendorStatus::ACTIVE,
        ]);

        $miraKitchen->products()->createMany([
            [
                'name' => 'Big Plate Fried Rice',
                'description' => 'Mira Kitchen Product 1 description',
                'price' => 2000,
                'quantity' => 100,
                'status' => ProductStatus::ACTIVE,
                'type' => ProductType::FOOD,
            ],
            [
                'name' => 'Small Plate Fried Rice',
                'description' => 'Mira Kitchen Product 2 description',
                'price' => 1800,
                'quantity' => 200,
                'status' => ProductStatus::ACTIVE,
                'type' => ProductType::FOOD,
            ],
            [
                'name' => 'Big Plate Jollof Rice',
                'description' => 'Mira Kitchen Product 3 description',
                'price' => 2000,
                'quantity' => 300,
                'status' => ProductStatus::ACTIVE,
                'type' => ProductType::FOOD,
            ],
            [
                'name' => 'Small Plate Jollof Rice',
                'description' => 'Mira Kitchen Product 4 description',
                'price' => 1800,
                'quantity' => 400,
                'status' => ProductStatus::ACTIVE,
                'type' => ProductType::FOOD,
            ],
            [
                'name' => 'Fried Chicken',
                'description' => 'Mira Kitchen Product 5 description',
                'price' => 3500,
                'quantity' => 50,
                'status' => ProductStatus::ACTIVE,
                'type' => ProductType::FOOD,
            ],
            [
                'name' => 'Plantain',
                'description' => 'Mira Kitchen Product 6 description',
                'price' => 500,
                'quantity' => 20,
                'is_addon' => true,
                'status' => ProductStatus::ACTIVE,
                'type' => ProductType::FOOD,
            ],
            [
                'name' => 'Salad',
                'description' => 'Mira Kitchen Product 6 description',
                'price' => 500,
                'quantity' => 10,
                'is_addon' => true,
                'status' => ProductStatus::ACTIVE,
                'type' => ProductType::FOOD,
            ],
        ]);

        $dbestSharwama = User::factory()->create();

        // Third Vendor
        $dbestSharwama = Vendor::create([
            'user_id' => $dbestSharwama->id,
            'name' => 'D best Sharwama',
            'email' => 'vendor3@example.com',
            'phone' => '1234567890',
            'address' => '1234567890',
            'website' => 'https://example.com',
            'description' => 'Sharwama Kitchen description',
            'status' => VendorStatus::ACTIVE,
        ]);

        $dbestSharwama->products()->createMany([
            [
                'name' => 'Sharwama with Beef',
                'description' => 'D best Sharwama Product 1 description',
                'price' => 3500,
                'quantity' => 100,
                'type' => ProductType::FOOD,
                'status' => ProductStatus::ACTIVE,
            ],
            [
                'name' => 'Sharwama with Chicken',
                'description' => 'D best Sharwama Product 2 description',
                'price' => 3400,
                'quantity' => 100,
                'type' => ProductType::FOOD,
            ],
            [
                'name' => 'Shombo Sharwama with chicken and Meat',
                'description' => 'D best Sharwama Product 2 description',
                'price' => 5000,
                'quantity' => 100,
                'type' => ProductType::FOOD,
            ],
        ]);

        $jamesLaundry = User::factory()->create();

        // Fourth Vendor
        $jamesLaundry = Vendor::create([
            'user_id' => $jamesLaundry->id,
            'name' => 'James Laundry',
            'email' => 'jameslaundry@example.com',
            'phone' => '1234567890',
            'address' => '1234567890',
            'website' => 'https://example.com',
            'description' => 'James Laundry Kitchen description',
            'status' => VendorStatus::ACTIVE,
        ]);

        $jamesLaundry->products()->createMany([
            [
                'name' => 'Shirt',
                'description' => 'James Laundry Product 1 description',
                'price' => 100,
                'quantity' => 100,
                'type' => ProductType::LAUNDRY,
            ],
            [
                'name' => 'Trouser',
                'description' => 'James Laundry Product 2 description',
                'price' => 200,
                'quantity' => 100,
                'type' => ProductType::LAUNDRY,
            ],
            [
                'name' => 'Shoe',
                'description' => 'James Laundry Product 3 description',
                'price' => 300,
                'quantity' => 100,
                'type' => ProductType::LAUNDRY,
            ],
            [
                'name' => 'Dress',
                'description' => 'James Laundry Product 4 description',
                'price' => 400,
                'quantity' => 100,
                'type' => ProductType::LAUNDRY,
            ],
            [
                'name' => 'Bedsheet',
                'description' => 'James Laundry Product 5 description',
                'price' => 500,
                'quantity' => 100,
                'type' => ProductType::LAUNDRY,
            ],
            [
                'name' => 'Towel',
                'description' => 'James Laundry Product 6 description',
                'price' => 600,
                'quantity' => 100,
                'type' => ProductType::LAUNDRY,
            ],
        ]);
    }
}
