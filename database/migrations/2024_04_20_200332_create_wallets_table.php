<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->decimal('balance', 18, 8)->default(0);
            // Primary display currency (ISO 4217) - tests expect this
            $table->string('currency', 3)->default('USD');
            $table->string('btc_address')->nullable();
            $table->string('xmr_address')->nullable();
            $table->string('sol_address')->nullable();
            $table->string('btc_qr')->nullable();
            $table->string('xmr_qr')->nullable();
            $table->string('sol_qr')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->boolean('is_default')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('wallets');
    }
};
