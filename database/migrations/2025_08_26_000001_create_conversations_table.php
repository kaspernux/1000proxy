<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('direct'); // direct | group
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('privacy')->default('private'); // private | public | internal
            $table->boolean('allow_attachments')->default(true);
            $table->boolean('allow_reactions')->default(true);
            $table->foreignId('created_by_id')->nullable();
            $table->string('created_by_type')->nullable();
            $table->timestamps();
            $table->index(['created_by_type', 'created_by_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
