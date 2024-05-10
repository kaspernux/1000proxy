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
            $table->integer('up');
            $table->integer('down');
            $table->integer('total');
            $table->string('remark');
            $table->boolean('enable');
            $table->timestamp('expiryTime')->nullable();
            $table->json('clientStats')->nullable();
            $table->string('listen');
            $table->integer('port');
            $table->string('protocol');
            $table->json('settings');
            $table->json('streamSettings');
            $table->string('tag');
            $table->json('sniffing');
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
