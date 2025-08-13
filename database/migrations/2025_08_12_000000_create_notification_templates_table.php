<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('name');
            $table->string('channel')->default('telegram');
            $table->string('locale', 8)->default('en');
            $table->string('subject')->nullable();
            $table->text('body');
            $table->boolean('enabled')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['key','channel','locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
