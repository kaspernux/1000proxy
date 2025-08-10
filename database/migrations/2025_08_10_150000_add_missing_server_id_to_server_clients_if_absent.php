<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('server_clients') && ! Schema::hasColumn('server_clients', 'server_id')) {
            Schema::table('server_clients', function (Blueprint $table) {
                $table->unsignedBigInteger('server_id')->nullable()->index()->after('id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('server_clients') && Schema::hasColumn('server_clients', 'server_id')) {
            Schema::table('server_clients', function (Blueprint $table) {
                $table->dropColumn('server_id');
            });
        }
    }
};
