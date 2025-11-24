<?php

use App\Models\Order;
use App\Models\Wallet;
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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');

            $table->foreignIdFor(Order::class);
            $table->foreignIdFor(Wallet::class);

            $table->string('amount');
            $table->string('currency');
            $table->string('reference');
            $table->string('payment_method');
            $table->string('payment_status');
            $table->string('payment_id')->nullable();
            $table->string('payment_url')->nullable();
            $table->string('flow');
            $table->string('status');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
