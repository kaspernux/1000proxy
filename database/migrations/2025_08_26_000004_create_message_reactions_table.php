<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('message_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('reactor_id');
            $table->string('reactor_type'); // App\Models\User | App\Models\Customer
            $table->string('emoji', 16); // store unicode emoji or shortcode
            $table->timestamps();
            $table->unique(['message_id','reactor_id','reactor_type','emoji'], 'message_reaction_unique');
            $table->index(['reactor_type','reactor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_reactions');
    }
};
