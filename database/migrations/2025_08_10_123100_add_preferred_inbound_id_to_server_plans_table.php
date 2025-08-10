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
                $table->foreignId('preferred_inbound_id')
                    ->nullable()
                    ->after('on_sale')
                    ->constrained('server_inbounds')
                    ->nullOnDelete();
                $table->index('preferred_inbound_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('server_plans', function (Blueprint $table) {
            if (Schema::hasColumn('server_plans', 'preferred_inbound_id')) {
                $table->dropForeign(['preferred_inbound_id']);
                $table->dropIndex(['preferred_inbound_id']);
                $table->dropColumn('preferred_inbound_id');
            }
        });
    }
};
