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
        Schema::table('servers', function (Blueprint $table) {
            // Add 3X-UI session and authentication management
            $table->string('session_cookie')->nullable()->after('alert_settings');
            $table->timestamp('session_expires_at')->nullable()->after('session_cookie');
            $table->timestamp('last_login_at')->nullable()->after('session_expires_at');
            $table->integer('login_attempts')->default(0)->after('last_login_at');
            $table->timestamp('last_login_attempt_at')->nullable()->after('login_attempts');

            // Add 3X-UI API configuration and status
            $table->string('api_version')->nullable()->after('last_login_attempt_at');
            $table->string('web_base_path')->nullable()->after('api_version'); // For custom base paths
            $table->json('api_capabilities')->nullable()->after('web_base_path'); // Supported features
            $table->integer('api_timeout')->default(30)->after('api_capabilities'); // Request timeout in seconds
            $table->integer('api_retry_count')->default(3)->after('api_timeout');
            $table->json('api_rate_limits')->nullable()->after('api_retry_count');

            // Add monitoring and statistics fields
            $table->json('global_traffic_stats')->nullable()->after('api_rate_limits'); // Overall server traffic
            $table->integer('total_inbounds')->default(0)->after('global_traffic_stats');
            $table->integer('active_inbounds')->default(0)->after('total_inbounds');
            $table->integer('total_online_clients')->default(0)->after('active_inbounds');
            $table->timestamp('last_global_sync_at')->nullable()->after('total_online_clients');

            // Add operational settings
            $table->boolean('auto_sync_enabled')->default(true)->after('last_global_sync_at');
            $table->integer('sync_interval_minutes')->default(5)->after('auto_sync_enabled');
            $table->boolean('auto_cleanup_depleted')->default(false)->after('sync_interval_minutes');
            $table->boolean('backup_notifications_enabled')->default(false)->after('auto_cleanup_depleted');
            $table->json('monitoring_thresholds')->nullable()->after('backup_notifications_enabled');

            // Add indexes for performance
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
        Schema::table('servers', function (Blueprint $table) {
            $table->dropIndex(['session_expires_at']);
            $table->dropIndex(['last_login_at']);
            $table->dropIndex(['auto_sync_enabled']);
            $table->dropIndex(['last_global_sync_at']);

            $table->dropColumn([
                'session_cookie',
                'session_expires_at',
                'last_login_at',
                'login_attempts',
                'last_login_attempt_at',
                'api_version',
                'web_base_path',
                'api_capabilities',
                'api_timeout',
                'api_retry_count',
                'api_rate_limits',
                'global_traffic_stats',
                'total_inbounds',
                'active_inbounds',
                'total_online_clients',
                'last_global_sync_at',
                'auto_sync_enabled',
                'sync_interval_minutes',
                'auto_cleanup_depleted',
                'backup_notifications_enabled',
                'monitoring_thresholds',
            ]);
        });
    }
};
