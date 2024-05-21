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
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('server_id')->constrained('servers')->onDelete('cascade');
            $table->foreignId('server_plan_id')->nullable()->constrained('server_plans')->onDelete('cascade');
            $table->foreignId('server_inbound_id')->nullable()->constrained('server_inbounds')->onDelete('cascade');
            $table->string('token');
            $table->foreignId('payments_id')->nullable()->constrained('payments')->onDelete('cascade');
            $table->integer('fileid');
            $table->string('remark')->nullable();
            $table->string('uuid');
            $table->string('protocol');
            $table->integer('expire_date');
            $table->text('link');
            $table->integer('amount');
            $table->integer('status');
            $table->string('date');
            $table->integer('notif')->default(0);
            $table->integer('rahgozar')->default(0);
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