<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 20)->nullable()->after('telegram_last_name');
            }
            if (!Schema::hasColumn('users', 'theme_mode')) {
                $table->string('theme_mode', 10)->nullable()->default('system')->after('locale');
            }
            if (!Schema::hasColumn('users', 'email_notifications')) {
                $table->boolean('email_notifications')->default(true)->after('theme_mode');
            }
            if (!Schema::hasColumn('users', 'timezone')) {
                $table->string('timezone', 50)->nullable()->after('email_notifications');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['phone','theme_mode','email_notifications','timezone'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
