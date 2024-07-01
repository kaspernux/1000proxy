<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_method_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->text('order_description')->nullable();
            $table->decimal('price_amount', 15, 2);
            $table->decimal('price_currency', 15, 2);
            $table->string('pay_currency');
            $table->string('ipn_callback_url');
            $table->string('invoice_url');
            $table->string('success_url');
            $table->string('cancel_url')->nullable();
            $table->string('partially_paid_url')->nullable();
            $table->boolean('is_fixed_rate')->default(true);
            $table->boolean('is_fee_paid_by_user')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoices');
    }
}
