<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->enum('type', ['fixed','percent'])->default('fixed');
            $table->decimal('value', 10, 2)->default(0); // fixed amount or percent (0.10 = 10% stored as decimal?)
            $table->boolean('is_active')->default(true);
            $table->integer('usage_limit')->nullable()->comment('optional global usage limit');
            $table->integer('used_count')->default(0);
            $table->boolean('single_use_per_customer')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('coupons');
    }
};
