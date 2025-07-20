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
        Schema::create('server_clients', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('3X-UI client UUID');
            $table->foreignId('server_inbound_id')->constrained()->onDelete('cascade');
            $table->string('email')->nullable()->unique();
            $table->string('password')->nullable();
            $table->string('flow')->default('')->comment('3X-UI flow control');
            $table->integer('limit_ip')->default(0)->comment('3X-UI IP connection limit');
            $table->bigInteger('total_gb_bytes')->default(0)->comment('3X-UI traffic limit (totalGB in bytes)');
            $table->bigInteger('expiry_time')->default(0)->comment('3X-UI expiry timestamp (milliseconds)');
            $table->boolean('enable')->default(true)->comment('3X-UI client enabled status');
            $table->string('tg_id')->default('')->comment('3X-UI Telegram ID');
            $table->string('sub_id', 16)->nullable()->index()->comment('3X-UI subscription ID (subId)');
            $table->bigInteger('reset')->default(0)->comment('3X-UI reset counter/timestamp');
            // 3X-UI remote sync fields
            $table->integer('remote_client_id')->nullable()->index()->comment('3X-UI client stats ID');
            $table->integer('remote_inbound_id')->nullable()->index()->comment('3X-UI inbound ID');
            $table->bigInteger('remote_up')->default(0)->comment('3X-UI upload bytes');
            $table->bigInteger('remote_down')->default(0)->comment('3X-UI download bytes');
            $table->bigInteger('remote_total')->default(0)->comment('3X-UI total bytes');
            $table->timestamp('last_api_sync_at')->nullable()->index();
            $table->enum('api_sync_status', ['pending', 'success', 'error'])->default('pending')->index();
            $table->text('api_sync_error')->nullable();
            $table->json('api_sync_log')->nullable();
            $table->timestamp('last_traffic_sync_at')->nullable();
            $table->json('remote_client_config')->nullable()->comment('Full 3X-UI client configuration');
            $table->json('connection_ips')->nullable()->comment('Client IP addresses');
            $table->timestamp('last_ip_clear_at')->nullable();
            $table->boolean('is_online')->default(false)->index();
            $table->timestamp('last_online_check_at')->nullable();
            // Local management fields
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('provisioned_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('terminated_at')->nullable();
            $table->timestamp('last_connection_at')->nullable();
            $table->bigInteger('traffic_limit_mb')->nullable();
            $table->bigInteger('traffic_used_mb')->nullable();
            $table->decimal('traffic_percentage_used', 5, 2)->nullable();
            $table->json('connection_stats')->nullable();
            $table->json('performance_metrics')->nullable();
            $table->integer('connection_count')->nullable();
            $table->json('client_config')->nullable();
            $table->json('provisioning_log')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->timestamp('next_billing_at')->nullable();
            $table->decimal('renewal_price', 10, 2)->nullable();
            // Legacy QR and link fields
            $table->text('qr_code_sub')->nullable();
            $table->text('qr_code_sub_json')->nullable();
            $table->text('qr_code_client')->nullable();
            $table->text('client_link')->nullable();
            $table->text('remote_sub_link')->nullable();
            $table->text('remote_json_link')->nullable();
            // Legacy connection fields
            $table->string('security')->nullable();
            $table->string('pbk')->nullable();
            $table->string('fp')->nullable();
            $table->string('sni')->nullable();
            $table->string('sid')->nullable();
            $table->string('spx')->nullable();
            $table->string('grpc_service_name')->nullable();
            $table->string('network_type')->nullable();
            $table->string('tls_type')->nullable();
            $table->string('alpn')->nullable();
            $table->string('header_type')->nullable();
            $table->string('host')->nullable();
            $table->string('path')->nullable();
            $table->string('kcp_seed')->nullable();
            $table->string('kcp_type')->nullable();
            $table->softDeletes();
            $table->timestamps();
            // Indexes
            $table->index(['remote_inbound_id', 'email'], 'idx_remote_inbound_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_clients');
    }
};
