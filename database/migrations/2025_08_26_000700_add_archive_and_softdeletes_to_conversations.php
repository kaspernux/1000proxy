<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            if (!Schema::hasColumn('conversations', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('allow_reactions');
            }
            if (!Schema::hasColumn('conversations', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            if (Schema::hasColumn('conversations', 'archived_at')) {
                $table->dropColumn('archived_at');
            }
            if (Schema::hasColumn('conversations', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
