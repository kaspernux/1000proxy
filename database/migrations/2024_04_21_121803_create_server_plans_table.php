<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServerPlansTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('server_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_category_id')->constrained()->onDelete('cascade');
            $table->foreignId('server_brand_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('product_image')->nullable();
            $table->text('description')->nullable();
            $table->integer('capacity')->nullable();
            $table->decimal('price', 10, 2);
            $table->enum('type', ['single', 'multiple', 'dedicated']);
            $table->integer('days');
            $table->integer('volume');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('in_stock')->default(true);
            $table->boolean('on_sale')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_plans');
    }
}