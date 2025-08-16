<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('server_clients')) {
            Schema::table('server_clients', function (Blueprint $table) {
                if (!Schema::hasColumn('server_clients', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable()->index()->after('customer_id');
                }
                if (!Schema::hasColumn('server_clients', 'uuid')) {
                    $table->string('uuid')->nullable()->index()->after('id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('server_clients')) {
            Schema::table('server_clients', function (Blueprint $table) {
                if (Schema::hasColumn('server_clients', 'user_id')) {
                    $table->dropColumn('user_id');
                }
                if (Schema::hasColumn('server_clients', 'uuid')) {
                    $table->dropColumn('uuid');
                }
            });
        }
    }
};
