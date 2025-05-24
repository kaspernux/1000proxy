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
            $table->string('password');
            $table->string('tgId')->nullable();
            $table->string('refcode', 50)->nullable();
            $table->string('date', 50)->nullable();
            $table->string('phone', 15)->nullable();
            $table->unsignedBigInteger('refered_by')->nullable();
            $table->string('step', 1000)->default('none')->nullable();
            $table->string('freetrial', 10)->nullable();
            $table->dateTime('first_start')->nullable();
            $table->text('temp')->nullable();
            $table->integer('is_agent')->default(0);
            $table->string('discount_percent', 1000)->nullable();
            $table->dateTime('agent_date')->nullable();
            $table->string('spam_info', 500)->nullable();
            $table->string('locale')->nullable()->default('en');
            $table->string('theme_mode')->default('system');
            $table->boolean('email_notifications')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('timezone')->nullable();
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