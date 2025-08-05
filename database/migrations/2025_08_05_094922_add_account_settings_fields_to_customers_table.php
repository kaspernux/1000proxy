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
        Schema::table('customers', function (Blueprint $table) {
            // Only add fields that don't exist yet
            // date_of_birth, bio, website, company, avatar already exist
            
            // Notification Preferences
            $table->json('sms_notifications')->nullable()->after('email_notifications');
            
            // Privacy Settings
            $table->json('privacy_settings')->nullable()->after('sms_notifications');
            
            // Security Settings
            $table->boolean('two_factor_enabled')->default(false)->after('privacy_settings');
            $table->boolean('login_alerts')->default(true)->after('two_factor_enabled');
            
            // Additional Profile Fields
            $table->boolean('premium')->default(false)->after('login_alerts');
            $table->timestamp('premium_expires_at')->nullable()->after('premium');
            $table->json('account_stats')->nullable()->after('premium_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'sms_notifications', 
                'privacy_settings', 
                'two_factor_enabled', 
                'login_alerts',
                'premium',
                'premium_expires_at',
                'account_stats'
            ]);
        });
    }
};
