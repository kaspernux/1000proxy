<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('server_inbounds', function (Blueprint $table) {
            if (!Schema::hasColumn('server_inbounds', 'provisioning_enabled')) {
                $table->boolean('provisioning_enabled')->default(false)->after('allocate');
            }
            if (!Schema::hasColumn('server_inbounds', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('provisioning_enabled');
            }
            if (!Schema::hasColumn('server_inbounds', 'capacity')) {
                $table->integer('capacity')->nullable()->after('is_default');
            }
            if (!Schema::hasColumn('server_inbounds', 'current_clients')) {
                $table->integer('current_clients')->default(0)->after('capacity');
            }
            if (!Schema::hasColumn('server_inbounds', 'status')) {
                $table->string('status')->default('active')->after('current_clients');
            }
        });
    }

    public function down(): void
    {
        Schema::table('server_inbounds', function (Blueprint $table) {
            foreach (['status','current_clients','capacity','is_default','provisioning_enabled'] as $col) {
                if (Schema::hasColumn('server_inbounds', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
