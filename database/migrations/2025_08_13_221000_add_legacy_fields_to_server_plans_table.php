<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('server_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('server_plans', 'duration_days')) {
                $table->integer('duration_days')->nullable()->after('days');
            }
            if (!Schema::hasColumn('server_plans', 'max_connections')) {
                $table->integer('max_connections')->nullable()->after('concurrent_connections');
            }
            if (!Schema::hasColumn('server_plans', 'bandwidth_limit_gb')) {
                $table->integer('bandwidth_limit_gb')->nullable()->after('data_limit_gb');
            }
        });
    }

    public function down(): void
    {
        Schema::table('server_plans', function (Blueprint $table) {
            foreach (['duration_days','max_connections','bandwidth_limit_gb'] as $col) {
                if (Schema::hasColumn('server_plans', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
