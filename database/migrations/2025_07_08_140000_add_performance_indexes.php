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
        // Add indexes for better query performance
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['user_id', 'created_at']);
            $table->index(['payment_status', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index('grand_amount');
        });

        Schema::table('server_clients', function (Blueprint $table) {
            $table->index(['user_id', 'server_id']);
            $table->index(['server_id', 'is_active']);
            $table->index(['uuid']);
            $table->index(['created_at']);
            $table->index(['expires_at']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->index(['order_id', 'server_plan_id']);
            $table->index(['server_id', 'created_at']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['order_id']);
            $table->index(['payment_id']);
            $table->index(['status', 'created_at']);
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->index(['user_id', 'created_at']);
            $table->index(['type', 'created_at']);
            $table->index(['status']);
        });

        Schema::table('server_plans', function (Blueprint $table) {
            $table->index(['is_active', 'sort_order']);
            $table->index(['server_category_id', 'is_active']);
            $table->index(['server_brand_id', 'is_active']);
            $table->index(['price']);
        });

        Schema::table('servers', function (Blueprint $table) {
            $table->index(['is_active', 'sort_order']);
            $table->index(['server_category_id', 'is_active']);
            $table->index(['server_brand_id', 'is_active']);
            $table->index(['location']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index(['role', 'is_active']);
            $table->index(['created_at']);
            $table->index(['last_login_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropIndex(['payment_status', 'created_at']);
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['grand_amount']);
        });

        Schema::table('server_clients', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'server_id']);
            $table->dropIndex(['server_id', 'is_active']);
            $table->dropIndex(['uuid']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['expires_at']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['order_id', 'server_plan_id']);
            $table->dropIndex(['server_id', 'created_at']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['order_id']);
            $table->dropIndex(['payment_id']);
            $table->dropIndex(['status', 'created_at']);
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropIndex(['type', 'created_at']);
            $table->dropIndex(['status']);
        });

        Schema::table('server_plans', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'sort_order']);
            $table->dropIndex(['server_category_id', 'is_active']);
            $table->dropIndex(['server_brand_id', 'is_active']);
            $table->dropIndex(['price']);
        });

        Schema::table('servers', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'sort_order']);
            $table->dropIndex(['server_category_id', 'is_active']);
            $table->dropIndex(['server_brand_id', 'is_active']);
            $table->dropIndex(['location']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role', 'is_active']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['last_login_at']);
        });
    }
};
