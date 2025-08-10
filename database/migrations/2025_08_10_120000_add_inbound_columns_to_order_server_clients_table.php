<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_server_clients', function (Blueprint $table) {
            if (!Schema::hasColumn('order_server_clients', 'server_inbound_id')) {
                $table->foreignId('server_inbound_id')
                    ->nullable()
                    ->after('order_item_id')
                    ->constrained('server_inbounds')
                    ->nullOnDelete();
                $table->index('server_inbound_id');
            }
            if (!Schema::hasColumn('order_server_clients', 'dedicated_inbound_id')) {
                $table->foreignId('dedicated_inbound_id')
                    ->nullable()
                    ->after('server_inbound_id')
                    ->constrained('server_inbounds')
                    ->nullOnDelete();
                $table->index('dedicated_inbound_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_server_clients', function (Blueprint $table) {
            if (Schema::hasColumn('order_server_clients', 'dedicated_inbound_id')) {
                $table->dropForeign(['dedicated_inbound_id']);
                $table->dropIndex(['dedicated_inbound_id']);
                $table->dropColumn('dedicated_inbound_id');
            }
            if (Schema::hasColumn('order_server_clients', 'server_inbound_id')) {
                $table->dropForeign(['server_inbound_id']);
                $table->dropIndex(['server_inbound_id']);
                $table->dropColumn('server_inbound_id');
            }
        });
    }
};
