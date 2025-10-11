<?php

use App\Models\Conversation;
use App\Models\User;
use App\Models\Vendor;
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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid');

            $table->foreignIdFor(User::class);
            $table->foreignIdFor(Vendor::class)->nullable();
            $table->foreignIdFor(Conversation::class);

            $table->string('message_sid');
            $table->text('message');
            $table->string('direction')->nullable();
            $table->string('phone')->nullable();
            $table->string('profile_name')->nullable();
            $table->string('channel');
            $table->string('provider');
            $table->string('participant');
            $table->string('status');

            $table->boolean('is_processed')->default(false);

            $table->timestamp('timestamp')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
