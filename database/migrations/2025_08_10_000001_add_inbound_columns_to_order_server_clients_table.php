<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('order_server_clients')) {
            return; // Safety – original table must exist.
        }

        Schema::table('order_server_clients', function (Blueprint $table) {
            if (!Schema::hasColumn('order_server_clients', 'server_inbound_id')) {
                $table->foreignId('server_inbound_id')->nullable()->after('order_item_id')->constrained('server_inbounds')->nullOnDelete();
                $table->index('server_inbound_id');
            }
            if (!Schema::hasColumn('order_server_clients', 'dedicated_inbound_id')) {
                $table->foreignId('dedicated_inbound_id')->nullable()->after('server_inbound_id')->constrained('server_inbounds')->nullOnDelete();
                $table->index('dedicated_inbound_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('order_server_clients')) {
            return;
        }

        Schema::table('order_server_clients', function (Blueprint $table) {
            if (Schema::hasColumn('order_server_clients', 'dedicated_inbound_id')) {
                $table->dropConstrainedForeignId('dedicated_inbound_id');
            }
            if (Schema::hasColumn('order_server_clients', 'server_inbound_id')) {
                $table->dropConstrainedForeignId('server_inbound_id');
            }
        });
    }
};
