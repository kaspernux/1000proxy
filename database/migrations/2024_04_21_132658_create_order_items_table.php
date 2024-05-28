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
        Schema::create('order_items', function (Blueprint $table)
            {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('server_plan_id')->nullable()->constrained('server_plans')->onDelete('cascade');
            $table->foreignId('server_client_id')->nullable()->constrained('server_clients')->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_amount', 10, 2)->nullable();
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->integer('agent_bought')->default(0);
            $table->timestamps();
            });
        }

    /**
     * Reverse the migrations.
     */
    public function down(): void
        {
        Schema::dropIfExists('order_items');
        }
    };
