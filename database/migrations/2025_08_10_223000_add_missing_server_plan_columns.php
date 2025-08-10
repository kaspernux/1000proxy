<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('server_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('server_plans', 'preferred_inbound_id')) {
                $table->unsignedBigInteger('preferred_inbound_id')->nullable()->after('on_sale');
                $table->index('preferred_inbound_id');
            }
            if (!Schema::hasColumn('server_plans', 'max_clients')) {
                $table->integer('max_clients')->nullable()->after('capacity');
            }
            if (!Schema::hasColumn('server_plans', 'current_clients')) {
                $table->integer('current_clients')->default(0)->after('max_clients');
            }
            if (!Schema::hasColumn('server_plans', 'auto_provision')) {
                $table->boolean('auto_provision')->default(true)->after('on_sale');
            }
            if (!Schema::hasColumn('server_plans', 'provision_settings')) {
                $table->json('provision_settings')->nullable()->after('auto_provision');
            }
            if (!Schema::hasColumn('server_plans', 'data_limit_gb')) {
                $table->decimal('data_limit_gb', 10, 2)->nullable()->after('volume');
            }
            if (!Schema::hasColumn('server_plans', 'concurrent_connections')) {
                $table->integer('concurrent_connections')->nullable()->after('data_limit_gb');
            }
            if (!Schema::hasColumn('server_plans', 'performance_metrics')) {
                $table->json('performance_metrics')->nullable()->after('provision_settings');
            }
            if (!Schema::hasColumn('server_plans', 'trial_days')) {
                $table->integer('trial_days')->nullable()->after('days');
            }
            if (!Schema::hasColumn('server_plans', 'setup_fee')) {
                $table->decimal('setup_fee', 10, 2)->default(0)->after('price');
            }
            if (!Schema::hasColumn('server_plans', 'renewable')) {
                $table->boolean('renewable')->default(true)->after('auto_provision');
            }
        });
    }

    public function down(): void
    {
        Schema::table('server_plans', function (Blueprint $table) {
            foreach ([
                'preferred_inbound_id','max_clients','current_clients','auto_provision','provision_settings',
                'data_limit_gb','concurrent_connections','performance_metrics','trial_days','setup_fee','renewable'
            ] as $col) {
                if (Schema::hasColumn('server_plans', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
