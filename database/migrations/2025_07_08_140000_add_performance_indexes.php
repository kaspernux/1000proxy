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
            $table->index(['payment_status', 'created_at']);
            $table->index(['order_status', 'created_at']);
            $table->index(['grand_amount']);
        });

        Schema::table('server_clients', function (Blueprint $table) {
            $table->index(['server_inbound_id', 'created_at']);
            $table->index(['created_at']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->index(['order_id']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['order_id']);
            $table->index(['payment_status', 'created_at']);
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->index(['created_at']);
            $table->index(['status']);
        });

        Schema::table('server_plans', function (Blueprint $table) {
            $table->index(['is_active']);
            $table->index(['server_category_id', 'is_active']);
            $table->index(['server_brand_id', 'is_active']);
            $table->index(['price']);
        });

        Schema::table('servers', function (Blueprint $table) {
            $table->index(['is_active']);
            $table->index(['server_category_id', 'is_active']);
            $table->index(['server_brand_id', 'is_active']);
        });

        Schema::table('users', function (Blueprint $table) {
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
            $table->dropIndex(['payment_status', 'created_at']);
            $table->dropIndex(['order_status', 'created_at']);
            $table->dropIndex(['grand_amount']);
        });

        Schema::table('server_clients', function (Blueprint $table) {
            $table->dropIndex(['server_inbound_id', 'created_at']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['order_id']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['order_id']);
            $table->dropIndex(['status', 'created_at']);
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['status']);
        });

        Schema::table('server_plans', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['server_category_id', 'is_active']);
            $table->dropIndex(['server_brand_id', 'is_active']);
            $table->dropIndex(['price']);
        });

        Schema::table('servers', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['server_category_id', 'is_active']);
            $table->dropIndex(['server_brand_id', 'is_active']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['last_login_at']);
        });
    }
};