<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('wallet_transactions', 'currency')) {
            Schema::table('wallet_transactions', function (Blueprint $table) {
                $table->string('currency', 10)->nullable()->after('amount');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('wallet_transactions', 'currency')) {
            Schema::table('wallet_transactions', function (Blueprint $table) {
                $table->dropColumn('currency');
            });
        }
    }
};
