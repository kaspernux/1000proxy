<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_ads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->nullable()->constrained('servers')->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->text('body')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_ads');
    }
};
