<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('currency', 10);
            $table->decimal('balance', 18, 8)->default(0);
            $table->string('address')->unique();
            $table->string('deposit_tag')->nullable(); // for coins like Monero
            $table->string('network')->nullable(); // BTC, XMR, SOL
            $table->string('qr_code_path')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('wallets');
    }
};
