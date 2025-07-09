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
        Schema::table('server_inbounds', function (Blueprint $table) {
            // Add missing 3X-UI API fields
            $table->string('tag')->nullable()->after('status_message');
            $table->string('listen')->nullable()->after('tag'); // Binding IP address
            $table->text('remote_settings')->nullable()->after('listen'); // Raw 3X-UI settings JSON string
            $table->text('remote_stream_settings')->nullable()->after('remote_settings'); // Raw 3X-UI streamSettings JSON string
            $table->text('remote_sniffing')->nullable()->after('remote_stream_settings'); // Raw 3X-UI sniffing JSON string
            $table->text('remote_allocate')->nullable()->after('remote_sniffing'); // Raw 3X-UI allocate JSON string

            // Add API synchronization fields
            $table->integer('remote_id')->nullable()->after('remote_allocate'); // 3X-UI inbound ID
            $table->timestamp('last_api_sync_at')->nullable()->after('remote_id');
            $table->json('api_sync_log')->nullable()->after('last_api_sync_at');
            $table->string('api_sync_status')->default('pending')->after('api_sync_log'); // pending, success, error
            $table->text('api_sync_error')->nullable()->after('api_sync_status');

            // Add traffic monitoring fields from 3X-UI
            $table->bigInteger('remote_up')->default(0)->after('api_sync_error'); // Upload bytes from 3X-UI
            $table->bigInteger('remote_down')->default(0)->after('remote_up'); // Download bytes from 3X-UI
            $table->bigInteger('remote_total')->default(0)->after('remote_down'); // Total bytes from 3X-UI
            $table->timestamp('last_traffic_sync_at')->nullable()->after('remote_total');

            // Add indexes for performance
            $table->index('remote_id');
            $table->index('tag');
            $table->index('api_sync_status');
            $table->index('last_api_sync_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('server_inbounds', function (Blueprint $table) {
            $table->dropIndex(['remote_id']);
            $table->dropIndex(['tag']);
            $table->dropIndex(['api_sync_status']);
            $table->dropIndex(['last_api_sync_at']);

            $table->dropColumn([
                'tag',
                'listen',
                'remote_settings',
                'remote_stream_settings',
                'remote_sniffing',
                'remote_allocate',
                'remote_id',
                'last_api_sync_at',
                'api_sync_log',
                'api_sync_status',
                'api_sync_error',
                'remote_up',
                'remote_down',
                'remote_total',
                'last_traffic_sync_at',
            ]);
        });
    }
};
