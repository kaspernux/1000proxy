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
        Schema::create('mobile_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('device_identifier')->unique();
            $table->string('device_name')->nullable();
            $table->string('device_type')->default('mobile'); // mobile, tablet, web
            $table->string('platform')->nullable(); // android, ios, web
            $table->string('platform_version')->nullable();
            $table->string('app_version')->nullable();
            $table->text('push_token')->nullable();
            $table->boolean('push_notifications_enabled')->default(true);
            $table->string('timezone')->default('UTC');
            $table->string('language', 5)->default('en');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->bigInteger('offline_data_size')->nullable();
            $table->integer('sync_version')->default(1);
            $table->string('sync_status')->default('pending');
            $table->timestamps();

            $table->index(['customer_id', 'is_active']);
            $table->index(['device_identifier']);
            $table->index(['last_seen_at']);
            $table->index(['push_notifications_enabled', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mobile_devices');
    }
};
