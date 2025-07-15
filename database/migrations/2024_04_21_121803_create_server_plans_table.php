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
        Schema::create('server_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->onDelete('cascade');

            // Advanced filtering fields
            $table->unsignedBigInteger('server_brand_id')->nullable();
            $table->foreign('server_brand_id')->references('id')->on('server_brands')->onDelete('set null');
            $table->index('server_brand_id');

            $table->unsignedBigInteger('server_category_id')->nullable();
            $table->foreign('server_category_id')->references('id')->on('server_categories')->onDelete('set null');
            $table->index('server_category_id');

            $table->string('country_code', 2)->nullable()->comment('ISO 3166-1 alpha-2 country code');
            $table->index('country_code');

            $table->string('region')->nullable()->comment('Region/State/Province');
            $table->index('region');

            $table->enum('protocol', ['vless', 'vmess', 'trojan', 'shadowsocks', 'mixed'])->default('vless');
            $table->index('protocol');

            $table->integer('bandwidth_mbps')->nullable()->comment('Bandwidth in Mbps');
            $table->index('bandwidth_mbps');

            $table->boolean('supports_ipv6')->default(false);
            $table->index('supports_ipv6');

            $table->integer('popularity_score')->default(0)->comment('Calculated popularity score');
            $table->index('popularity_score');

            $table->enum('server_status', ['online', 'offline', 'maintenance'])->default('online');
            $table->index('server_status');

            // Product info
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('product_image')->nullable();
            $table->text('description')->nullable();
            $table->integer('capacity')->nullable();
            $table->decimal('price', 10, 2);
            $table->enum('type', ['single', 'multiple', 'dedicated', 'branded']);
            $table->integer('days');
            $table->integer('volume');

            // Status flags
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('in_stock')->default(true);
            $table->boolean('on_sale')->default(true);

            $table->timestamps();

            // Composite indexes for advanced filtering
            $table->index(['country_code', 'server_category_id'], 'idx_location_category');
            $table->index(['server_brand_id', 'protocol'], 'idx_brand_protocol');
            $table->index(['price', 'is_active'], 'idx_price_active');
            $table->index(['popularity_score', 'server_status'], 'idx_popular_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_plans');
    }
};
