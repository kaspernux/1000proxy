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
        Schema::table('downloadable_items', function (Blueprint $table) {
            // Add new enhanced fields
            $table->string('name')->nullable()->after('server_id');
            $table->text('description')->nullable()->after('name');

            // Download controls
            $table->integer('current_downloads')->default(0)->after('download_limit');

            // Access controls
            $table->enum('access_type', ['public', 'customer', 'order'])->default('customer')->after('expiration_time');
            $table->boolean('is_active')->default(true)->after('access_type');
            $table->boolean('require_authentication')->default(true)->after('is_active');
            $table->boolean('track_downloads')->default(true)->after('require_authentication');

            // Security
            $table->string('download_token')->nullable()->after('track_downloads');
            $table->string('checksum')->nullable()->after('download_token');
            $table->json('allowed_ips')->nullable()->after('checksum');

            // File metadata
            $table->bigInteger('file_size')->nullable()->after('allowed_ips');
            $table->string('mime_type')->nullable()->after('file_size');
            $table->string('version')->nullable()->after('mime_type');
            $table->text('changelog')->nullable()->after('version');
            $table->string('category')->nullable()->after('changelog');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('downloadable_items', function (Blueprint $table) {
            $table->dropColumn([
                'name',
                'description',
                'current_downloads',
                'access_type',
                'is_active',
                'require_authentication',
                'track_downloads',
                'download_token',
                'checksum',
                'allowed_ips',
                'file_size',
                'mime_type',
                'version',
                'changelog',
                'category',
            ]);
        });
    }
};
