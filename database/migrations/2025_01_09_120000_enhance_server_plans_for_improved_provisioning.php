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
        Schema::table('server_plans', function (Blueprint $table) {
            // Enhanced provisioning configuration
            $table->unsignedBigInteger('preferred_inbound_id')->nullable()->after('server_id');
            $table->integer('max_clients')->nullable()->after('capacity');
            $table->integer('current_clients')->default(0)->after('max_clients');
            $table->boolean('auto_provision')->default(true)->after('on_sale');
            $table->json('provision_settings')->nullable()->after('auto_provision');

            // Performance and monitoring
            $table->decimal('data_limit_gb', 10, 2)->nullable()->after('volume');
            $table->integer('concurrent_connections')->nullable()->after('data_limit_gb');
            $table->json('performance_metrics')->nullable()->after('provision_settings');

            // Business logic
            $table->integer('trial_days')->default(0)->after('days');
            $table->decimal('setup_fee', 8, 2)->default(0)->after('price');
            $table->boolean('renewable')->default(true)->after('auto_provision');

            // Add foreign key constraint for preferred_inbound_id
            $table->foreign('preferred_inbound_id')->references('id')->on('server_inbounds')->nullOnDelete();

            // Add indexes for performance
            $table->index(['server_id', 'is_active']);
            $table->index(['preferred_inbound_id']);
            $table->index(['current_clients', 'max_clients']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('server_plans', function (Blueprint $table) {
            $table->dropForeign(['preferred_inbound_id']);
            $table->dropIndex(['server_id', 'is_active']);
            $table->dropIndex(['preferred_inbound_id']);
            $table->dropIndex(['current_clients', 'max_clients']);

            $table->dropColumn([
                'preferred_inbound_id',
                'max_clients',
                'current_clients',
                'auto_provision',
                'provision_settings',
                'data_limit_gb',
                'concurrent_connections',
                'performance_metrics',
                'trial_days',
                'setup_fee',
                'renewable'
            ]);
        });
    }
};
