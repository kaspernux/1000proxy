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
        Schema::table('servers', function (Blueprint $table) {
            // Add structured panel connection fields for 3X-UI API
            $table->string('host')->nullable()->after('status');
            $table->integer('panel_port')->default(2053)->after('host');

            // Make panel_url nullable for backward compatibility
            $table->string('panel_url')->nullable()->change();

            // Add indexes for performance
            $table->index(['host', 'panel_port']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn(['host', 'panel_port']);
            $table->string('panel_url')->nullable(false)->change();
        });
    }
};
