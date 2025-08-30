<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds nullable normalized foreign keys to the addresses table so we can
     * reference countries, cities and postal_codes when a worldwide dataset
     * is available. Foreign keys are added only when the referenced tables
     * exist to avoid migration ordering issues.
     */
    public function up(): void
    {
        if (!Schema::hasTable('addresses')) {
            return;
        }

        Schema::table('addresses', function (Blueprint $table) {
            if (!Schema::hasColumn('addresses', 'country_id')) {
                $table->unsignedBigInteger('country_id')->nullable()->after('country')->index();
            }
            if (!Schema::hasColumn('addresses', 'city_id')) {
                $table->unsignedBigInteger('city_id')->nullable()->after('city')->index();
            }
            if (!Schema::hasColumn('addresses', 'postal_code_id')) {
                $table->unsignedBigInteger('postal_code_id')->nullable()->after('postal_code')->index();
            }
        });

        // Add foreign keys where possible (non-blocking if referenced tables don't exist).
        // Some environments (e.g. older Laravel DB connections) may not expose the Doctrine
        // schema manager. We avoid relying on it and instead attempt to add the FK inside
        // a try/catch so migrations don't fail on platforms where the referenced tables
        // aren't present yet.
        try {
            if (Schema::hasTable('countries') && Schema::hasColumn('addresses', 'country_id')) {
                Schema::table('addresses', function (Blueprint $table) {
                    $table->foreign('country_id')->references('id')->on('countries')->nullOnDelete();
                });
            }
        } catch (\Throwable $e) {
            // non-fatal: FK will be added later if needed
        }

        try {
            if (Schema::hasTable('cities') && Schema::hasColumn('addresses', 'city_id')) {
                Schema::table('addresses', function (Blueprint $table) {
                    $table->foreign('city_id')->references('id')->on('cities')->nullOnDelete();
                });
            }
        } catch (\Throwable $e) {
            // non-fatal
        }

        try {
            if (Schema::hasTable('postal_codes') && Schema::hasColumn('addresses', 'postal_code_id')) {
                Schema::table('addresses', function (Blueprint $table) {
                    $table->foreign('postal_code_id')->references('id')->on('postal_codes')->nullOnDelete();
                });
            }
        } catch (\Throwable $e) {
            // non-fatal
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('addresses')) {
            return;
        }

        Schema::table('addresses', function (Blueprint $table) {
            if (Schema::hasColumn('addresses', 'country_id')) {
                // drop foreign if exists
                try { $table->dropForeign(['country_id']); } catch (\Throwable $e) {}
                $table->dropColumn('country_id');
            }
            if (Schema::hasColumn('addresses', 'city_id')) {
                try { $table->dropForeign(['city_id']); } catch (\Throwable $e) {}
                $table->dropColumn('city_id');
            }
            if (Schema::hasColumn('addresses', 'postal_code_id')) {
                try { $table->dropForeign(['postal_code_id']); } catch (\Throwable $e) {}
                $table->dropColumn('postal_code_id');
            }
        });
    }
};
