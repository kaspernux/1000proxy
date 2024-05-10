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
        Schema::create('server_infos', function (Blueprint $table)
            {
            $table->id();
            $table->string('title');
            $table->integer('ucount')->default(0);
            $table->text('remark')->nullable();
            $table->string('flag')->nullable();
            $table->boolean('active')->default(false);
            $table->tinyInteger('state')->default(0); // 0: inactive, 1: active, 2: suspended
            $table->timestamps();
            });
        }

    /**
     * Reverse the migrations.
     */
    public function down(): void
        {
        Schema::dropIfExists('server_infos');
        }
    };