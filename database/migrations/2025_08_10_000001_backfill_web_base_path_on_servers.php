<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Backfill NULL web_base_path values to root '/'
        DB::table('servers')->whereNull('web_base_path')->update(['web_base_path' => '/']);
    }

    public function down(): void
    {
        // No rollback action; safe noop
    }
};
