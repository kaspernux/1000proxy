<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
    public function up(): void {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->nullable()->constrained('wallets')->nullOnDelete();            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete(); // ✅ fixed here
            $table->string('type', 20); // deposit, withdrawal, payment, refund, etc.
            $table->decimal('amount', 18, 8);
            $table->string('status', 20)->default('pending'); // pending, completed, failed
            $table->string('reference')->unique();
            $table->string('address')->unique()->nullable(); // Unique address for the transaction
            $table->string('payment_id')->nullable();
            $table->json('metadata')->nullable();
            $table->string('description')->nullable(); // ✅ safe
            $table->string('qr_code_path')->nullable(); // Path to the QR code image
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('wallet_transactions');
    }
};
