<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('client_metrics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('server_client_id'); // UUID from server_clients.id
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('server_id')->nullable();
            $table->boolean('is_online')->default(false);
            $table->unsignedInteger('latency_ms')->nullable();
            $table->unsignedBigInteger('total_bytes')->nullable();
            $table->timestamp('measured_at')->useCurrent();
            $table->timestamps();

            $table->index(['server_client_id', 'measured_at']);
            $table->index(['customer_id', 'measured_at']);
            $table->index(['server_id', 'measured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_metrics');
    }
};
