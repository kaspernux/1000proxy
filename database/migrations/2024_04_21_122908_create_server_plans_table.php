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
        Schema::create('server_plans', function (Blueprint $table)
            {
            $table->id();
            $table->foreignId('brand_id')->constrained()->onDelete('cascade');
            $table->foreignId('server_id')->constrained()->onDelete('cascade');
            $table->foreignId('server_inbound_id')->constrained()->onDelete('cascade');
            $table->foreignId('server_category_id')->constrained()->onDelete('cascade');
            $table->string('fileid');
            $table->string('acount');
            $table->integer('limitip');
            $table->string('title');
            $table->string('protocol');
            $table->integer('days');
            $table->bigInteger('volume');
            $table->string('type');
            $table->float('price');
            $table->text('descr');
            $table->string('pic');
            $table->boolean('active')->default(false);
            $table->integer('step');
            $table->timestamp('date')->nullable();
            $table->string('rahgozar');
            $table->string('dest');
            $table->string('serverNames');
            $table->string('spiderX');
            $table->string('flow');
            $table->string('custom_path')->nullable();
            $table->integer('custom_port')->nullable();
            $table->string('custom_sni')->nullable();
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
    };
