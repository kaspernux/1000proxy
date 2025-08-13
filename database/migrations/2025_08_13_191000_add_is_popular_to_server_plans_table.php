<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('server_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('server_plans', 'is_popular')) {
                $table->boolean('is_popular')->default(false)->after('is_featured');
                $table->index('is_popular');
            }
        });
    }

    public function down(): void
    {
        Schema::table('server_plans', function (Blueprint $table) {
            if (Schema::hasColumn('server_plans', 'is_popular')) {
                try {
                    $table->dropIndex(['is_popular']);
                } catch (\Throwable $e) {
                    // Index name may differ; ignore
                }
                $table->dropColumn('is_popular');
            }
        });
    }
};
