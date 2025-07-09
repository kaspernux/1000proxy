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
            // Capacity and provisioning management
            $table->integer('capacity')->nullable()->after('total');
            $table->integer('current_clients')->default(0)->after('capacity');
            $table->boolean('is_default')->default(false)->after('enable');
            $table->boolean('provisioning_enabled')->default(true)->after('is_default');

            // Performance monitoring
            $table->json('performance_metrics')->nullable()->after('allocate');
            $table->bigInteger('traffic_limit_bytes')->nullable()->after('performance_metrics');
            $table->bigInteger('traffic_used_bytes')->default(0)->after('traffic_limit_bytes');

            // Configuration and settings
            $table->json('client_template')->nullable()->after('traffic_used_bytes');
            $table->json('provisioning_rules')->nullable()->after('client_template');
            $table->timestamp('last_sync_at')->nullable()->after('provisioning_rules');

            // Status tracking
            $table->enum('status', ['active', 'inactive', 'maintenance', 'full'])->default('active')->after('last_sync_at');
            $table->text('status_message')->nullable()->after('status');

            // Add indexes for performance
            $table->index(['server_id', 'provisioning_enabled']);
            $table->index(['current_clients', 'capacity']);
            $table->index(['is_default', 'provisioning_enabled']);
            $table->index(['status', 'enable']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('server_inbounds', function (Blueprint $table) {
            $table->dropIndex(['server_id', 'provisioning_enabled']);
            $table->dropIndex(['current_clients', 'capacity']);
            $table->dropIndex(['is_default', 'provisioning_enabled']);
            $table->dropIndex(['status', 'enable']);

            $table->dropColumn([
                'capacity',
                'current_clients',
                'is_default',
                'provisioning_enabled',
                'performance_metrics',
                'traffic_limit_bytes',
                'traffic_used_bytes',
                'client_template',
                'provisioning_rules',
                'last_sync_at',
                'status',
                'status_message'
            ]);
        });
    }
};
