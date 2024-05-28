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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->decimal('grand_amount', 20, 8); // Adjusting precision for cryptocurrency
            $table->string('currency');
            $table->foreignId('payment_method_id')->constrained()->onDelete('cascade');
            $table->string('transaction_id');
            $table->enum('payment_status', ['pending','paid','failed'])->default('pending');
            $table->enum('order_status', ['new','processing','completed','dispute'])->default('new');
            $table->date('order_date');
            $table->text('notes')->nullable(); // Allow notes to be nullable if not always provided
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
