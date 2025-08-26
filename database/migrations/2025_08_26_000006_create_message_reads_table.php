<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('message_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('reader_id');
            $table->string('reader_type');
            $table->timestamp('read_at');
            $table->timestamps();
            $table->unique(['message_id','reader_id','reader_type']);
            $table->index(['reader_type','reader_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_reads');
    }
};
