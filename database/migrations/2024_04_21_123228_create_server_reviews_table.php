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
        Schema::create('server_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('server_id');
            $table->unsignedBigInteger('customer_id');
            $table->text('comments')->nullable();
            $table->boolean('approved')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_reviews');
    }
};