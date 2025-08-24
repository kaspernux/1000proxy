<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('activity_logs')) {
            return; // nothing to do
        }

        Schema::table('activity_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('activity_logs', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->after('user_id')->constrained('customers')->nullOnDelete();
            }
        });

        // Migrate legacy integer column `customer` into `customer_id` if present
        if (Schema::hasColumn('activity_logs', 'customer')) {
            try {
                DB::statement('UPDATE activity_logs SET customer_id = customer WHERE customer_id IS NULL');
            } catch (\Throwable $e) {
                // ignore if statement fails
            }

            // Drop legacy column if safe
            try {
                Schema::table('activity_logs', function (Blueprint $table) {
                    $table->dropColumn('customer');
                });
            } catch (\Throwable $e) {
                // ignore if not droppable without doctrine/dbal
            }
        }
    }

    public function down(): void
    {
        // Non-destructive: keep customer_id
    }
};
