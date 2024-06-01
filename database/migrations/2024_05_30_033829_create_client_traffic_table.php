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
        Schema::create('client_traffics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('cascade');
            $table->foreignId('server_inbound_id')->nullable()->constrained('server_plans')->onDelete('cascade');
            $table->boolean('enable')->default(false);
            $table->string('email')->nullable();
            $table->integer('up')->nullable();
            $table->integer('down')->nullable();
            $table->dateTime('expiryTime')->nullable();
            $table->integer('total')->nullable();
            $table->boolean('reset')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_traffics');

  }
};