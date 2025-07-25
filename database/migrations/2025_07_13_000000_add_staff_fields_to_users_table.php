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
        Schema::table('users', function (Blueprint $table) {
            // Add staff role field if it doesn't exist
            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['admin', 'support_manager', 'sales_support'])
                    ->default('support_manager')
                    ->after('password');
            }

            // Add active status field if it doesn't exist
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('role');
            }

            // Add last login tracking if it doesn't exist
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('is_active');
            }

            // Add Telegram integration fields if they don't exist
            if (!Schema::hasColumn('users', 'telegram_chat_id')) {
                $table->bigInteger('telegram_chat_id')->nullable()->after('last_login_at');
            }

            if (!Schema::hasColumn('users', 'telegram_username')) {
                $table->string('telegram_username')->nullable()->after('telegram_chat_id');
            }

            if (!Schema::hasColumn('users', 'telegram_first_name')) {
                $table->string('telegram_first_name')->nullable()->after('telegram_username');
            }

            if (!Schema::hasColumn('users', 'telegram_last_name')) {
                $table->string('telegram_last_name')->nullable()->after('telegram_first_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'is_active',
                'last_login_at',
                'telegram_chat_id',
                'telegram_username',
                'telegram_first_name',
                'telegram_last_name'
            ]);
        });
    }
};
