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
        Schema::table('server_clients', function (Blueprint $table) {
            // Add missing 3X-UI client fields based on API structure
            $table->string('flow')->nullable()->after('password'); // Flow control field from 3X-UI
            $table->string('tg_id')->nullable()->after('tgId'); // Telegram ID (rename existing tgId)
            $table->string('sub_id')->nullable()->after('tg_id'); // Subscription ID (rename existing subId)
            $table->integer('limit_ip')->default(0)->after('sub_id'); // IP connection limit (rename existing limitIp)
            $table->bigInteger('total_gb_bytes')->default(0)->after('limit_ip'); // Traffic limit in bytes (not GB)
            $table->timestamp('expiry_time')->nullable()->after('total_gb_bytes'); // Expiration time (rename existing expiryTime)
            $table->bigInteger('reset_counter')->default(0)->after('expiry_time'); // Reset counter/timestamp

            // Add 3X-UI API synchronization fields
            $table->integer('remote_client_id')->nullable()->after('reset_counter'); // 3X-UI client stats ID
            $table->integer('remote_inbound_id')->nullable()->after('remote_client_id'); // 3X-UI inbound ID
            $table->timestamp('last_api_sync_at')->nullable()->after('remote_inbound_id');
            $table->json('api_sync_log')->nullable()->after('last_api_sync_at');
            $table->string('api_sync_status')->default('pending')->after('api_sync_log'); // pending, success, error
            $table->text('api_sync_error')->nullable()->after('api_sync_status');

            // Add traffic monitoring fields from 3X-UI API
            $table->bigInteger('remote_up')->default(0)->after('api_sync_error'); // Upload bytes from 3X-UI
            $table->bigInteger('remote_down')->default(0)->after('remote_up'); // Download bytes from 3X-UI
            $table->bigInteger('remote_total')->default(0)->after('remote_down'); // Total bytes from 3X-UI
            $table->timestamp('last_traffic_sync_at')->nullable()->after('remote_total');

            // Add client configuration and management fields
            $table->json('remote_client_config')->nullable()->after('last_traffic_sync_at'); // Full 3X-UI client config
            $table->json('connection_ips')->nullable()->after('remote_client_config'); // IP addresses used by client
            $table->timestamp('last_ip_clear_at')->nullable()->after('connection_ips');
            $table->boolean('is_online')->default(false)->after('last_ip_clear_at'); // Current online status
            $table->timestamp('last_online_check_at')->nullable()->after('is_online');

            // Add indexes for performance
            $table->index('remote_client_id');
            $table->index('remote_inbound_id');
            $table->index('api_sync_status');
            $table->index('last_api_sync_at');
            $table->index('is_online');
            $table->index(['remote_inbound_id', 'email']); // Composite index for 3X-UI queries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('server_clients', function (Blueprint $table) {
            $table->dropIndex(['remote_client_id']);
            $table->dropIndex(['remote_inbound_id']);
            $table->dropIndex(['api_sync_status']);
            $table->dropIndex(['last_api_sync_at']);
            $table->dropIndex(['is_online']);
            $table->dropIndex(['remote_inbound_id', 'email']);

            $table->dropColumn([
                'flow',
                'tg_id',
                'sub_id',
                'limit_ip',
                'total_gb_bytes',
                'expiry_time',
                'reset_counter',
                'remote_client_id',
                'remote_inbound_id',
                'last_api_sync_at',
                'api_sync_log',
                'api_sync_status',
                'api_sync_error',
                'remote_up',
                'remote_down',
                'remote_total',
                'last_traffic_sync_at',
                'remote_client_config',
                'connection_ips',
                'last_ip_clear_at',
                'is_online',
                'last_online_check_at',
            ]);
        });
    }
};
