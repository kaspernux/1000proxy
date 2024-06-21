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
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->decimal('grand_amount', 10, 2);
            $table->string('currency', 3);
            $table->foreignId('payment_method_id')->constrained('payment_methods')->onDelete('cascade');
            $table->enum('payment_status', ['pending','paid','failed'])->default('pending');
            $table->enum('order_status', ['new','processing','completed','dispute'])->default('new');
            $table->date('order_date');
            $table->text('notes')->nullable();
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
