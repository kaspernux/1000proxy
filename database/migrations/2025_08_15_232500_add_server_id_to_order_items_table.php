<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'server_id')) {
                $table->foreignId('server_id')->nullable()->after('server_plan_id');
                // No FK to avoid migration conflicts; server can be resolved via plan as well
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'server_id')) {
                $table->dropColumn('server_id');
            }
        });
    }
};
