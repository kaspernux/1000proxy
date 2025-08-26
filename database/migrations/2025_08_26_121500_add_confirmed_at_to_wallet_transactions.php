<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('wallet_transactions', 'confirmed_at')) {
            Schema::table('wallet_transactions', function (Blueprint $table) {
                $table->timestamp('confirmed_at')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('wallet_transactions', 'confirmed_at')) {
            Schema::table('wallet_transactions', function (Blueprint $table) {
                $table->dropColumn('confirmed_at');
            });
        }
    }
};
