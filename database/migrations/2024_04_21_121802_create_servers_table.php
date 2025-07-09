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
            $table->string('name')->nullable();
            $table->string('username');
            $table->string('password');
            $table->foreignId('server_category_id')->constrained()->onDelete('cascade')->nullable();
            $table->foreignId('server_brand_id')->constrained()->onDelete('cascade')->nullable();
            $table->string('country')->nullable();
            $table->string('flag')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['up', 'down', 'paused'])->default('up');
            $table->string('host'); // Separate HOST field for 3X-UI API
            $table->integer('panel_port')->default(2053); // Separate PORT field for 3X-UI panel
            $table->string('web_base_path')->nullable(); // WEBBASEPATH for 3X-UI panel
            $table->string('panel_url')->nullable(); // Keep for backward compatibility
            $table->string('ip');
            $table->integer('port')->nullable();
            $table->string('sni')->nullable();
            $table->string('header_type')->nullable();
            $table->string('request_header')->nullable();
            $table->string('response_header')->nullable();
            $table->string('security')->nullable();
            $table->json('tlsSettings')->nullable();
            $table->string('type')->nullable();
            $table->string('port_type')->nullable();
            $table->string('reality')->nullable();
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
