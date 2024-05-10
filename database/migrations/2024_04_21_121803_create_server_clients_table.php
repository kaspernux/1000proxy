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
            $table->unsignedBigInteger('inbound_id');
            $table->foreign('inbound_id')->references('id')->on('server_inbounds')->onDelete('cascade');
            $table->unsignedBigInteger('up')->default(0);
            $table->unsignedBigInteger('down')->default(0);
            $table->unsignedBigInteger('total')->default(0);
            $table->string('remark')->nullable();
            $table->boolean('enable')->default(true);
            $table->dateTime('expiryTime')->nullable();
            $table->string('listen')->nullable();
            $table->integer('port')->nullable();
            $table->string('protocol')->nullable();
            $table->json('settings')->nullable();
            $table->json('streamSettings')->nullable();
            $table->string('tag')->nullable();
            $table->json('sniffing')->nullable();
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
