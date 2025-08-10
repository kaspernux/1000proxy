<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('agent_bought')->index();
            }
        });

        // Optional best-effort backfill using related server_clients expiry_time (milliseconds) if schema present
        if (Schema::hasTable('server_clients') && Schema::hasColumn('server_clients', 'expiry_time')) {
            // MySQL-specific backfill selecting the minimum expiry_time per order_id + plan mapping
            try {
                DB::statement(<<<SQL
UPDATE order_items oi
JOIN (
    SELECT sc.order_id, sc.plan_id, MIN(NULLIF(sc.expiry_time,0)) AS min_expiry
    FROM server_clients sc
    WHERE sc.expiry_time IS NOT NULL AND sc.expiry_time > 0
    GROUP BY sc.order_id, sc.plan_id
) agg ON agg.order_id = oi.order_id AND agg.plan_id = oi.server_plan_id
SET oi.expires_at = FROM_UNIXTIME(agg.min_expiry / 1000)
WHERE oi.expires_at IS NULL;
SQL);
            } catch (Throwable $e) {
                // Silently ignore if database driver doesn't support the statement; manual backfill can be run later.
            }
        }
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'expires_at')) {
                $table->dropColumn('expires_at');
            }
        });
    }
};
