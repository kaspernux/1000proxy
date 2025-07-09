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
            // Enhanced XUI configuration
            $table->json('xui_config')->nullable()->after('reality');
            $table->json('connection_settings')->nullable()->after('xui_config');
            $table->timestamp('last_connected_at')->nullable()->after('connection_settings');
            $table->enum('health_status', ['healthy', 'warning', 'critical', 'offline'])->default('healthy')->after('status');

            // Performance monitoring
            $table->json('performance_metrics')->nullable()->after('health_status');
            $table->integer('total_clients')->default(0)->after('performance_metrics');
            $table->integer('active_clients')->default(0)->after('total_clients');
            $table->bigInteger('total_traffic_mb')->default(0)->after('active_clients');

            // Management settings
            $table->boolean('auto_provisioning')->default(true)->after('total_traffic_mb');
            $table->integer('max_clients_per_inbound')->default(100)->after('auto_provisioning');
            $table->json('provisioning_rules')->nullable()->after('max_clients_per_inbound');

            // Monitoring and alerts
            $table->timestamp('last_health_check_at')->nullable()->after('provisioning_rules');
            $table->text('health_message')->nullable()->after('last_health_check_at');
            $table->json('alert_settings')->nullable()->after('health_message');

            // Add indexes for performance
            $table->index(['status', 'health_status']);
            $table->index(['auto_provisioning', 'status']);
            $table->index(['last_connected_at']);
            $table->index(['total_clients', 'active_clients']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropIndex(['status', 'health_status']);
            $table->dropIndex(['auto_provisioning', 'status']);
            $table->dropIndex(['last_connected_at']);
            $table->dropIndex(['total_clients', 'active_clients']);

            $table->dropColumn([
                'xui_config',
                'connection_settings',
                'last_connected_at',
                'health_status',
                'performance_metrics',
                'total_clients',
                'active_clients',
                'total_traffic_mb',
                'auto_provisioning',
                'max_clients_per_inbound',
                'provisioning_rules',
                'last_health_check_at',
                'health_message',
                'alert_settings'
            ]);
        });
    }
};
