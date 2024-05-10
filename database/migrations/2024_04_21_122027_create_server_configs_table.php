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
        Schema::create('server_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->onDelete('cascade');
            $table->string('panel_url');
            $table->string('ip');
            $table->string('sni');
            $table->string('header_type');
            $table->string('request_header');
            $table->string('response_header');
            $table->string('security');
            $table->string('tlsSettings');
            $table->string('type');
            $table->string('username');
            $table->string('password');
            $table->string('port_type');
            $table->string('reality');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_configs');
    }
};
