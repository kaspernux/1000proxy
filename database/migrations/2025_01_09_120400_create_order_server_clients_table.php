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
        Schema::create('order_server_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('server_client_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_item_id')->nullable()->constrained()->onDelete('cascade');

            // Provisioning status and tracking
            $table->enum('provision_status', ['pending', 'provisioning', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->text('provision_error')->nullable();
            $table->integer('provision_attempts')->default(0);
            $table->timestamp('provision_started_at')->nullable();
            $table->timestamp('provision_completed_at')->nullable();

            // Client configuration tracking
            $table->json('provision_config')->nullable();
            $table->json('provision_log')->nullable();
            $table->decimal('provision_duration_seconds', 8, 2)->nullable();

            // Quality assurance
            $table->boolean('qa_passed')->nullable();
            $table->text('qa_notes')->nullable();
            $table->timestamp('qa_completed_at')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index(['order_id', 'provision_status']);
            $table->index(['server_client_id', 'provision_status']);
            $table->index(['provision_status', 'provision_started_at']);
            $table->unique(['order_id', 'server_client_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_server_clients');
    }
};
