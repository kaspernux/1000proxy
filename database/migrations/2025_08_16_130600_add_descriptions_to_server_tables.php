<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('server_categories') && !Schema::hasColumn('server_categories', 'description')) {
            Schema::table('server_categories', function (Blueprint $table) {
                $table->text('description')->nullable()->after('name');
            });
        }

        if (Schema::hasTable('server_brands') && !Schema::hasColumn('server_brands', 'description')) {
            Schema::table('server_brands', function (Blueprint $table) {
                $table->text('description')->nullable()->after('slug');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('server_categories') && Schema::hasColumn('server_categories', 'description')) {
            Schema::table('server_categories', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }

        if (Schema::hasTable('server_brands') && Schema::hasColumn('server_brands', 'description')) {
            Schema::table('server_brands', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }
    }
};
