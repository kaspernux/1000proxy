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
        Schema::create('server_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('server_inbound_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->longText('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->uuid('client_id');
            $table->integer('alter_id')->default(0);
            $table->string('email');
            $table->integer('limit_ip')->nullable();
            $table->bigInteger('total_gb');
            $table->bigInteger('expiry_time');
            $table->string('tg_id')->nullable();
            $table->string('sub_id')->nullable();
            $table->text('qr_code_sub')->nullable();
            $table->text('qr_code_sub_json')->nullable();
            $table->text('qr_code_client')->nullable();
            $table->boolean('enabled')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('in_stock')->default(true);
            $table->bigInteger('capacity')->default(0);
            $table->boolean('reset')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_clients');

  }
};