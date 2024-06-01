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
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('hash_id')->unique();
            $table->text('description')->nullable();
            $table->string('type')->nullable();
            $table->integer('volume')->nullable();
            $table->integer('day')->nullable();
            $table->decimal('amount', 15, 2);
            $table->decimal('tron_price', 15, 2)->nullable();
            $table->date('request_date')->nullable();
            $table->enum('state', ['new','processing','completed','failed'])->default('new');
            $table->integer('agent_bought')->nullable();
            $table->integer('agent_count')->nullable();
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