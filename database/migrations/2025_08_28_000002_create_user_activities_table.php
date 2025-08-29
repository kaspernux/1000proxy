<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('action');
            $table->text('description')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('user_activities');
    }
};
