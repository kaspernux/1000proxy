<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('analytics_events')) {
            Schema::create('analytics_events', function (Blueprint $table) {
                $table->id();
                $table->string('event_type');
                $table->string('device_type')->nullable();
                $table->string('user_agent')->nullable();
                $table->string('ip')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['event_type', 'device_type']);
                $table->index('created_at');
            });
        }
    }

    public function down(): void {
        Schema::dropIfExists('analytics_events');
    }
};
