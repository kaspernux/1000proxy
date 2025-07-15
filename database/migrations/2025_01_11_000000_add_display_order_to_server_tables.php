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
        // Add display_order to server_brands table
        Schema::table('server_brands', function (Blueprint $table) {
            $table->integer('display_order')->default(0)->after('is_active');
            $table->index('display_order');
        });

        // Add display_order to server_categories table
        Schema::table('server_categories', function (Blueprint $table) {
            $table->integer('display_order')->default(0)->after('is_active');
            $table->index('display_order');
        });

        // Add display_order to server_plans table
        Schema::table('server_plans', function (Blueprint $table) {
            $table->integer('display_order')->default(0)->after('is_featured');
            $table->index('display_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('server_brands', function (Blueprint $table) {
            $table->dropIndex(['display_order']);
            $table->dropColumn('display_order');
        });

        Schema::table('server_categories', function (Blueprint $table) {
            $table->dropIndex(['display_order']);
            $table->dropColumn('display_order');
        });

        Schema::table('server_plans', function (Blueprint $table) {
            $table->dropIndex(['display_order']);
            $table->dropColumn('display_order');
        });
    }
};
