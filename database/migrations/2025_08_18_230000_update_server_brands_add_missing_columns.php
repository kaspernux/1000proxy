<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('server_brands', function (Blueprint $table) {
            if (!Schema::hasColumn('server_brands', 'description')) {
                $table->text('description')->nullable()->after('slug');
            }
            if (!Schema::hasColumn('server_brands', 'website_url')) {
                $table->string('website_url')->nullable()->after('image');
            }
            if (!Schema::hasColumn('server_brands', 'support_url')) {
                $table->string('support_url')->nullable()->after('website_url');
            }
            if (!Schema::hasColumn('server_brands', 'tier')) {
                $table->string('tier')->nullable()->after('support_url');
            }
            if (!Schema::hasColumn('server_brands', 'brand_color')) {
                $table->string('brand_color', 32)->nullable()->after('tier');
            }
            if (!Schema::hasColumn('server_brands', 'featured')) {
                $table->boolean('featured')->default(false)->after('brand_color');
            }
            if (!Schema::hasColumn('server_brands', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('featured');
            }
        });

        // If legacy `desc` exists and `description` is now present, copy values over once.
        if (Schema::hasColumn('server_brands', 'desc') && Schema::hasColumn('server_brands', 'description')) {
            // Use query builder with raw expression to safely reference reserved column name `desc`.
            DB::table('server_brands')->whereNull('description')->update([
                'description' => DB::raw('`desc`')
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('server_brands', function (Blueprint $table) {
            if (Schema::hasColumn('server_brands', 'sort_order')) {
                $table->dropColumn('sort_order');
            }
            if (Schema::hasColumn('server_brands', 'featured')) {
                $table->dropColumn('featured');
            }
            if (Schema::hasColumn('server_brands', 'brand_color')) {
                $table->dropColumn('brand_color');
            }
            if (Schema::hasColumn('server_brands', 'tier')) {
                $table->dropColumn('tier');
            }
            if (Schema::hasColumn('server_brands', 'support_url')) {
                $table->dropColumn('support_url');
            }
            if (Schema::hasColumn('server_brands', 'website_url')) {
                $table->dropColumn('website_url');
            }
            if (Schema::hasColumn('server_brands', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
