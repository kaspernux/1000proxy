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
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('server_category_id')->constrained()->onDelete('cascade');
            $table->string('country');
            $table->string('flag');
            $table->string('ip_address');
            $table->string('panel_url');
            $table->integer('port');
            $table->string('username');
            $table->string('password');
            $table->text('description')->nullable();
            $table->enum('status', ['up', 'down', 'paused'])->default('up');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};