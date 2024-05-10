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
        Schema::create('inventory_logs', function (Blueprint $table)
            {
            $table->id();
            $table->foreignId('server_plan_id')->constrained()->onDelete('cascade');
            $table->integer('quantity_change');
            $table->string('reason');
            $table->timestamps();
            });
        }

    /**
     * Reverse the migrations.
     */
    public function down(): void
        {
        Schema::dropIfExists('inventory_logs');
        }
    };
