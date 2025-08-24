<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            if (!Schema::hasColumn('servers', 'response_time_ms')) {
                $table->unsignedInteger('response_time_ms')->nullable()->after('last_health_check_at');
            }
            if (!Schema::hasColumn('servers', 'uptime_percentage')) {
                $table->decimal('uptime_percentage', 5, 2)->nullable()->after('response_time_ms');
            }
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            if (Schema::hasColumn('servers', 'uptime_percentage')) {
                $table->dropColumn('uptime_percentage');
            }
            if (Schema::hasColumn('servers', 'response_time_ms')) {
                $table->dropColumn('response_time_ms');
            }
        });
    }
};
