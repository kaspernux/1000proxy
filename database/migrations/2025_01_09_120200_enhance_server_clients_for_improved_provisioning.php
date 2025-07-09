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
            // Order and customer associations
            $table->unsignedBigInteger('order_id')->nullable()->after('server_inbound_id');
            $table->unsignedBigInteger('customer_id')->nullable()->after('order_id');

            // Client lifecycle management
            $table->enum('status', ['provisioning', 'active', 'suspended', 'expired', 'terminated'])->default('provisioning')->after('enable');
            $table->timestamp('provisioned_at')->nullable()->after('status');
            $table->timestamp('activated_at')->nullable()->after('provisioned_at');
            $table->timestamp('suspended_at')->nullable()->after('activated_at');
            $table->timestamp('terminated_at')->nullable()->after('suspended_at');
            $table->timestamp('last_connection_at')->nullable()->after('terminated_at');

            // Enhanced traffic management
            $table->bigInteger('traffic_limit_mb')->nullable()->after('totalGb');
            $table->bigInteger('traffic_used_mb')->default(0)->after('traffic_limit_mb');
            $table->decimal('traffic_percentage_used', 5, 2)->default(0)->after('traffic_used_mb');

            // Performance and monitoring
            $table->json('connection_stats')->nullable()->after('traffic_percentage_used');
            $table->json('performance_metrics')->nullable()->after('connection_stats');
            $table->integer('connection_count')->default(0)->after('performance_metrics');
            $table->timestamp('last_traffic_sync_at')->nullable()->after('connection_count');

            // Configuration and settings
            $table->json('client_config')->nullable()->after('last_traffic_sync_at');
            $table->json('provisioning_log')->nullable()->after('client_config');
            $table->text('error_message')->nullable()->after('provisioning_log');
            $table->integer('retry_count')->default(0)->after('error_message');

            // Business logic
            $table->boolean('auto_renew')->default(false)->after('retry_count');
            $table->timestamp('next_billing_at')->nullable()->after('auto_renew');
            $table->decimal('renewal_price', 8, 2)->nullable()->after('next_billing_at');

            // Add foreign key constraints
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();

            // Add indexes for performance
            $table->index(['order_id', 'customer_id']);
            $table->index(['status', 'expiryTime']);
            $table->index(['customer_id', 'status']);
            $table->index(['last_connection_at']);
            $table->index(['traffic_percentage_used']);
            $table->index(['next_billing_at', 'auto_renew']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('server_clients', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropForeign(['customer_id']);

            $table->dropIndex(['order_id', 'customer_id']);
            $table->dropIndex(['status', 'expiryTime']);
            $table->dropIndex(['customer_id', 'status']);
            $table->dropIndex(['last_connection_at']);
            $table->dropIndex(['traffic_percentage_used']);
            $table->dropIndex(['next_billing_at', 'auto_renew']);

            $table->dropColumn([
                'order_id',
                'customer_id',
                'status',
                'provisioned_at',
                'activated_at',
                'suspended_at',
                'terminated_at',
                'last_connection_at',
                'traffic_limit_mb',
                'traffic_used_mb',
                'traffic_percentage_used',
                'connection_stats',
                'performance_metrics',
                'connection_count',
                'last_traffic_sync_at',
                'client_config',
                'provisioning_log',
                'error_message',
                'retry_count',
                'auto_renew',
                'next_billing_at',
                'renewal_price'
            ]);
        });
    }
};
