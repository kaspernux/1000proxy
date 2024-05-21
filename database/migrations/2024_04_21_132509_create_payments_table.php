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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('hash_id');
            $table->string('description');
            $table->string('payment_method_id')->constrained()->onDelete('cascade');;
            $table->string('type');
            $table->foreignId('server_plan_id')->constrained()->onDelete('cascade');
            $table->integer('volume');
            $table->integer('day');
            $table->decimal('price', 10, 2);
            $table->decimal('tron_price', 10, 2);
            $table->timestamp('request_date');
            $table->string('state');
            $table->integer('agent_bought');
            $table->integer('agent_count');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
