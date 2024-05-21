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
        Schema::create('customers', function (Blueprint $table)
            {
            $table->id();
            $table->boolean('is_active')->default(true);
            $table->string('image')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('telegram_id')->nullable();
            $table->string('refcode', 50);
            $table->integer('wallet')->default(0);
            $table->string('date', 50);
            $table->string('phone', 15)->nullable();
            $table->unsignedBigInteger('refered_by')->nullable();
            $table->string('step', 1000)->default('none');
            $table->string('freetrial', 10)->nullable();
            $table->string('first_start', 10)->nullable();
            $table->text('temp')->nullable();
            $table->integer('is_agent')->default(0);
            $table->string('discount_percent', 1000)->nullable();
            $table->integer('agent_date')->default(0);
            $table->string('spam_info', 500)->nullable();
            $table->rememberToken();
            $table->timestamps();
            });
        }

    /**
     * Reverse the migrations.
     */
    public function down(): void
        {
        Schema::dropIfExists('customers');
        }
    };