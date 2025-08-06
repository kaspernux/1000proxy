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
        Schema::table('orders', function (Blueprint $table) {
            // Order identification - without unique constraint initially
            $table->string('order_number')->nullable()->after('id');
            $table->enum('status', ['pending', 'processing', 'completed', 'cancelled', 'dispute'])->default('pending')->after('order_status');
            
            // Financial fields
            $table->decimal('subtotal', 10, 2)->default(0)->after('grand_amount');
            $table->decimal('tax_amount', 10, 2)->default(0)->after('subtotal');
            $table->decimal('shipping_amount', 10, 2)->default(0)->after('tax_amount');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('shipping_amount');
            $table->decimal('total_amount', 10, 2)->default(0)->after('discount_amount');
            
            // Billing information
            $table->string('billing_first_name')->nullable()->after('total_amount');
            $table->string('billing_last_name')->nullable()->after('billing_first_name');
            $table->string('billing_email')->nullable()->after('billing_last_name');
            $table->string('billing_phone')->nullable()->after('billing_email');
            $table->string('billing_company')->nullable()->after('billing_phone');
            $table->text('billing_address')->nullable()->after('billing_company');
            $table->string('billing_city')->nullable()->after('billing_address');
            $table->string('billing_state')->nullable()->after('billing_city');
            $table->string('billing_postal_code')->nullable()->after('billing_state');
            $table->string('billing_country')->nullable()->after('billing_postal_code');
            
            // Coupon and additional info
            $table->string('coupon_code')->nullable()->after('billing_country');
            $table->string('payment_transaction_id')->nullable()->after('coupon_code');
        });

        // Update existing orders with order numbers
        \DB::statement("UPDATE orders SET order_number = CONCAT('ORD-', UPPER(HEX(RANDOM_BYTES(8)))) WHERE order_number IS NULL OR order_number = ''");
        
        // Add unique constraint after populating data
        Schema::table('orders', function (Blueprint $table) {
            $table->unique('order_number');
        });
    }    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'order_number',
                'status',
                'subtotal',
                'tax_amount',
                'shipping_amount',
                'discount_amount',
                'total_amount',
                'billing_first_name',
                'billing_last_name',
                'billing_email',
                'billing_phone',
                'billing_company',
                'billing_address',
                'billing_city',
                'billing_state',
                'billing_postal_code',
                'billing_country',
                'coupon_code',
                'payment_transaction_id'
            ]);
        });
    }
};
