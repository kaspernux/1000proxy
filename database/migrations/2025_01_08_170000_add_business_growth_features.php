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
        // Add partnership and success tracking fields to users table
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_affiliate')->default(false);
            $table->boolean('is_reseller')->default(false);
            $table->string('affiliate_code')->nullable()->unique();
            $table->decimal('health_score', 5, 2)->default(100.00);
            $table->timestamp('last_login_at')->nullable();
            $table->json('partnership_data')->nullable();
        });
        
        // Create affiliate referrals table
        Schema::create('affiliate_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('referred_user_id')->constrained('users')->onDelete('cascade');
            $table->string('referral_code');
            $table->enum('status', ['pending', 'confirmed', 'rejected'])->default('pending');
            $table->decimal('commission_amount', 10, 2)->default(0.00);
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            
            $table->index(['affiliate_id', 'status']);
            $table->index(['referred_user_id']);
            $table->index(['referral_code']);
        });
        
        // Create affiliate commissions table
        Schema::create('affiliate_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->decimal('commission_amount', 10, 2);
            $table->decimal('commission_rate', 5, 4);
            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            
            $table->index(['affiliate_id', 'status']);
            $table->index(['order_id']);
        });
        
        // Create user login tracking table
        Schema::create('user_logins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('ip_address');
            $table->string('user_agent');
            $table->date('login_date');
            $table->timestamps();
            
            $table->index(['user_id', 'login_date']);
            $table->index(['login_date']);
        });
        
        // Create customer success automation logs table
        Schema::create('automation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('automation_type');
            $table->string('action');
            $table->json('data')->nullable();
            $table->enum('status', ['success', 'failed', 'pending'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'automation_type']);
            $table->index(['automation_type', 'status']);
            $table->index(['created_at']);
        });
        
        // Create partnership integration logs table
        Schema::create('partnership_logs', function (Blueprint $table) {
            $table->id();
            $table->string('service_name');
            $table->string('action');
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->enum('status', ['success', 'failed']);
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['service_name', 'action']);
            $table->index(['status', 'created_at']);
        });
        
        // Add geographic expansion fields to server plans table
        Schema::table('server_plans', function (Blueprint $table) {
            $table->json('geographic_restrictions')->nullable();
            $table->json('regional_pricing')->nullable();
            $table->boolean('global_availability')->default(true);
        });
        
        // Add payment gateway fields to orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_gateway')->nullable();
            $table->json('payment_gateway_data')->nullable();
            $table->decimal('gateway_fee', 8, 2)->default(0.00);
            $table->string('gateway_transaction_id')->nullable();
        });
        
        // Add last used tracking to server clients
        Schema::table('server_clients', function (Blueprint $table) {
            $table->timestamp('last_used_at')->nullable();
            $table->date('last_used_date')->nullable();
            $table->timestamp('renewed_at')->nullable();
            $table->integer('usage_count')->default(0);
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_affiliate',
                'is_reseller',
                'affiliate_code',
                'health_score',
                'last_login_at',
                'partnership_data'
            ]);
        });
        
        Schema::dropIfExists('affiliate_referrals');
        Schema::dropIfExists('affiliate_commissions');
        Schema::dropIfExists('user_logins');
        Schema::dropIfExists('automation_logs');
        Schema::dropIfExists('partnership_logs');
        
        Schema::table('server_plans', function (Blueprint $table) {
            $table->dropColumn([
                'geographic_restrictions',
                'regional_pricing',
                'global_availability'
            ]);
        });
        
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_gateway',
                'payment_gateway_data',
                'gateway_fee',
                'gateway_transaction_id'
            ]);
        });
        
        Schema::table('server_clients', function (Blueprint $table) {
            $table->dropColumn([
                'last_used_at',
                'last_used_date',
                'renewed_at',
                'usage_count'
            ]);
        });
    }
};
