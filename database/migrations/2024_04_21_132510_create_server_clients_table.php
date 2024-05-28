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
            $table->foreignId('brand_id')->constrained()->onDelete('cascade');
            $table->foreignId('server_inbound_id')->constrained('server_inbounds')->onDelete('cascade');
            $table->foreignId('server_category_id')->constrained('server_categories')->onDelete('cascade');
            $table->string('name');
            $table->string('email', 255)->nullable();
            $table->json('images')->nullable();
            $table->longText('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('in_stock')->default(true);
            $table->boolean('enable')->default(true);
            $table->bigInteger('up')->default(0);
            $table->bigInteger('down')->default(0);
            $table->timestamp('expiry_time')->nullable();
            $table->bigInteger('total')->default(0);
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
