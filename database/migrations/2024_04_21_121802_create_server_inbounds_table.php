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
            $table->string('user_id');
            $table->bigInteger('up')->default(0);
            $table->bigInteger('down')->default(0);
            $table->bigInteger('total')->default(0);
            $table->string('remark', 255)->nullable();
            $table->boolean('enable')->default(false);
            $table->timestamp('expiryTime')->nullable();
            $table->json('clientStats')->nullable();
            $table->string('listen', 255)->nullable();
            $table->integer('port');
            $table->string('protocol', 50);
            $table->json('settings')->nullable();
            $table->json('streamSettings')->nullable();
            $table->string('tag', 100)->nullable();
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
