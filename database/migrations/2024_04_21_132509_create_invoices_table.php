<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTable extends Migration
{
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('iid')->nullable();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_method_id')->constrained()->onDelete('cascade');
            $table->string('payment_id')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('pay_address')->nullable();
            $table->decimal('price_amount', 10, 2);
            $table->string('price_currency');
            $table->decimal('pay_amount', 10, 2)->nullable();
            $table->string('pay_currency')->nullable();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->text('order_description')->nullable();
            $table->string('ipn_callback_url')->nullable();
            $table->string('invoice_url')->nullable();
            $table->string('success_url')->nullable();
            $table->string('cancel_url')->nullable();
            $table->string('partially_paid_url')->nullable();
            $table->string('purchase_id')->nullable();
            $table->decimal('amount_received', 10, 2)->nullable();
            $table->string('payin_extra_id')->nullable();
            $table->string('smart_contract')->nullable();
            $table->string('network')->nullable();
            $table->integer('network_precision')->nullable();
            $table->integer('time_limit')->nullable();
            $table->timestamp('expiration_estimate_date')->nullable();
            $table->boolean('is_fixed_rate')->default(false);
            $table->boolean('is_fee_paid_by_user')->default(true);
            $table->timestamp('valid_until')->nullable();
            $table->string('type')->nullable();
            $table->string('redirect_url')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('invoices');
    }
}