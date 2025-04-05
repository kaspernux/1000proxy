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
        Schema::create('server_inbounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->onDelete('cascade');
            $table->integer('userId');
            $table->bigInteger('up')->nullable();
            $table->bigInteger('down')->nullable();
            $table->bigInteger('total')->nullable();
            $table->string('remark')->nullable();
            $table->boolean('enable')->default(false);
            $table->datetime('expiryTime')->nullable();
            $table->json('clientStats')->nullable();
            $table->string('listen')->nullable();
            $table->integer('port')->nullable();
            $table->string('protocol')->nullable();
            $table->json('settings')->nullable();
            $table->json('streamSettings')->nullable();
            $table->string('tag')->unique()->nullable();
            $table->json('sniffing')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_inbounds');
    }
};
