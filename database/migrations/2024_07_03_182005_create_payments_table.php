<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('payment_id')->unique();
            $table->string('payment_status')->default('waiting');
            $table->string('pay_address');
            $table->float('price_amount');
            $table->string('price_currency');
            $table->float('pay_amount')->nullable();
            $table->string('pay_currency');
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('order_description')->nullable();
            $table->string('ipn_callback_url')->nullable();
            $table->string('purchase_id')->nullable();
            $table->float('amount_received')->nullable();
            $table->string('payin_extra_id')->nullable();
            $table->string('smart_contract')->nullable();
            $table->string('network')->nullable();
            $table->string('network_precision')->nullable();
            $table->string('time_limit')->nullable();
            $table->timestamp('expiration_estimate_date')->nullable();
            $table->boolean('is_fixed_rate')->default(false);
            $table->boolean('is_fee_paid_by_user')->default(false);
            $table->timestamp('valid_until')->nullable();
            $table->string('type');
            $table->string('redirect_url')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
}
