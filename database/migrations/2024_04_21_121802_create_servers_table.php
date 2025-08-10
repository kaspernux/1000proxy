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
            $table->boolean('is_active')->default(true);
            $table->foreignId('server_category_id')->constrained()->onDelete('cascade')->nullable();
            $table->foreignId('server_brand_id')->constrained()->onDelete('cascade')->nullable();
            $table->string('country')->nullable();
            $table->string('flag')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['up', 'down', 'paused'])->default('up');
            $table->string('host')->nullable()->comment('3X-UI panel host');
            $table->integer('panel_port')->default(2053)->comment('3X-UI panel port');
            $table->string('web_base_path')->nullable()->default('/')->comment('3X-UI web base path (e.g. / or /proxy)');
            $table->string('panel_url')->nullable();
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
            // 3X-UI session management
            $table->text('session_cookie')->nullable()->comment('3X-UI session cookie');
            $table->timestamp('session_expires_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->integer('login_attempts')->default(0);
            $table->timestamp('last_login_attempt_at')->nullable();
            // 3X-UI API configuration
            $table->string('api_version')->nullable();
            $table->json('api_capabilities')->nullable();
            $table->integer('api_timeout')->default(30);
            $table->integer('api_retry_count')->default(3);
            $table->json('api_rate_limits')->nullable();
            // 3X-UI statistics
            $table->json('global_traffic_stats')->nullable();
            $table->integer('total_inbounds')->default(0);
            $table->integer('active_inbounds')->default(0);
            $table->integer('total_online_clients')->default(0);
            $table->timestamp('last_global_sync_at')->nullable();
            // 3X-UI operational settings
            $table->boolean('auto_sync_enabled')->default(true);
            $table->json('xui_config')->nullable();
            $table->json('connection_settings')->nullable();
            $table->timestamp('last_connected_at')->nullable();
            $table->string('health_status')->nullable();
            $table->json('performance_metrics')->nullable();
            $table->integer('total_clients')->default(0);
            $table->integer('active_clients')->default(0);
            $table->bigInteger('total_traffic_mb')->default(0);
            $table->boolean('auto_provisioning')->default(false);
            $table->integer('max_clients_per_inbound')->nullable();
            $table->json('provisioning_rules')->nullable();
            $table->timestamp('last_health_check_at')->nullable();
            $table->string('health_message')->nullable();
            $table->json('alert_settings')->nullable();
            $table->integer('sync_interval_minutes')->nullable();
            $table->boolean('auto_cleanup_depleted')->default(false);
            $table->boolean('backup_notifications_enabled')->default(false);
            $table->json('monitoring_thresholds')->nullable();
            $table->timestamps();
            // Indexes
            $table->index('session_expires_at');
            $table->index('last_login_at');
            $table->index('auto_sync_enabled');
            $table->index('last_global_sync_at');
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
