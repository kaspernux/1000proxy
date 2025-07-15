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
            $table->bigInteger('up')->default(0);
            $table->bigInteger('down')->default(0);
            $table->bigInteger('total')->default(0);
            $table->string('remark');
            $table->boolean('enable')->default(true);
            $table->bigInteger('expiry_time')->default(0)->comment('3X-UI expiry timestamp (milliseconds)');
            $table->json('clientStats')->nullable();
            $table->string('listen')->nullable();
            $table->integer('port')->nullable();
            $table->string('protocol')->nullable();
            $table->json('settings')->nullable();
            $table->json('streamSettings')->nullable();
            $table->string('tag')->nullable()->index()->comment('3X-UI inbound tag identifier');
            $table->json('sniffing')->nullable();
            $table->json('allocate')->nullable();
            // 3X-UI remote sync fields
            $table->integer('remote_id')->nullable()->index()->comment('3X-UI inbound ID');
            $table->bigInteger('remote_up')->default(0)->comment('3X-UI upload bytes');
            $table->bigInteger('remote_down')->default(0)->comment('3X-UI download bytes');
            $table->bigInteger('remote_total')->default(0)->comment('3X-UI total bytes');
            $table->text('remote_settings')->nullable()->comment('3X-UI settings JSON string');
            $table->text('remote_stream_settings')->nullable()->comment('3X-UI streamSettings JSON string');
            $table->text('remote_sniffing')->nullable()->comment('3X-UI sniffing JSON string');
            $table->text('remote_allocate')->nullable()->comment('3X-UI allocate JSON string');
            // API sync tracking
            $table->timestamp('last_api_sync_at')->nullable()->index();
            $table->enum('api_sync_status', ['pending', 'success', 'error'])->default('pending')->index();
            $table->text('api_sync_error')->nullable();
            $table->json('api_sync_log')->nullable();
            // Traffic sync tracking
            $table->timestamp('last_traffic_sync_at')->nullable();
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
