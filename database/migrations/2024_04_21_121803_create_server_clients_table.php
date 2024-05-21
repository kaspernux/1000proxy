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
        Schema::create('server_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_inbound_id')->constrained('server_inbounds')->onDelete('cascade');
            $table->boolean('enable')->default(true);
            $table->string('email', 255)->nullable();
            $table->bigInteger('up')->default(0);
            $table->bigInteger('down')->default(0);
            $table->timestamp('expiry_time')->nullable();
            $table->bigInteger('total')->default(0);
            $table->boolean('reset')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_clients');
    }
};