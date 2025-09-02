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
        if (! Schema::hasColumn('servers', 'bandwidth_limit_gb')) {
            Schema::table('servers', function (Blueprint $table) {
                $table->integer('bandwidth_limit_gb')->nullable()->default(0)->after('max_clients');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('servers', 'bandwidth_limit_gb')) {
            Schema::table('servers', function (Blueprint $table) {
                $table->dropColumn('bandwidth_limit_gb');
            });
        }
    }
};
