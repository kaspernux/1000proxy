<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('participant_id');
            $table->string('participant_type'); // App\Models\User | App\Models\Customer
            $table->string('role')->default('member'); // member | admin | owner | agent
            $table->boolean('can_post')->default(true);
            $table->boolean('can_edit')->default(true);
            $table->boolean('can_delete')->default(false);
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
            $table->unique(['conversation_id','participant_id','participant_type'], 'conv_participant_unique');
            $table->index(['participant_type','participant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_participants');
    }
};
