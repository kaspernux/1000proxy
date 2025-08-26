<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('sender_id');
            $table->string('sender_type'); // App\Models\User | App\Models\Customer
            $table->text('body')->nullable();
            $table->json('meta')->nullable(); // for mentions, formatting, etc.
            $table->boolean('is_edited')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->boolean('is_deleted')->default(false); // hard-block delete for customers via policies
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->index(['sender_type','sender_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
