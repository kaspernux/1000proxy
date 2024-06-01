<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServerInboundsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('server_inbounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->onDelete('cascade');
            $table->string('user_id');
            $table->integer('up')->nullable();
            $table->integer('down')->nullable();
            $table->integer('total')->nullable();
            $table->string('remark')->nullable();
            $table->boolean('enable')->default(false);
            $table->dateTime('expiry_time')->nullable();
            $table->string('listen')->nullable();
            $table->integer('port')->nullable();
            $table->string('protocol')->nullable();
            $table->json('settings')->nullable();
            $table->json('stream_settings')->nullable();
            $table->boolean('sniffing')->default(false);
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
}
