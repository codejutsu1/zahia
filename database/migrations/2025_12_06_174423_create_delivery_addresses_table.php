<?php

use App\Enums\DeliveryAddressStatus;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('delivery_addresses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');

            $table->foreignIdFor(User::class);

            $table->string('room_number')->nullable();
            $table->string('floor_number')->nullable();
            $table->string('building_number')->nullable();
            $table->string('building_name')->nullable();
            $table->string('location')->nullable();
            $table->string('street_name')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            $table->text('description')->nullable();
            $table->string('landmark')->nullable();
            $table->string('instructions')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('status')->default(DeliveryAddressStatus::Active);

            $table->boolean('is_storey')->default(false);
            $table->boolean('is_estate')->default(false);
            $table->boolean('is_main')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_addresses');
    }
};
