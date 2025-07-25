<?php

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
        Schema::create('mobile_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('device_id')->constrained('mobile_devices')->onDelete('cascade');
            $table->string('session_token', 255); // Changed from text to string with length
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('expires_at');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'device_id']);
            $table->index(['session_token']); // Now this will work
            $table->index(['is_active', 'expires_at']);
            $table->index(['last_activity_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mobile_sessions');
    }
};
