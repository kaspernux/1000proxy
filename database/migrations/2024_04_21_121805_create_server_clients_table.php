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
        Schema::create('server_clients', function (Blueprint $table) {
            $table->id();

            $table->foreignId('server_inbound_id')
                ->constrained()
                ->onDelete('cascade');

            $table->string('email')->nullable()->unique();
            $table->string('password')->nullable(); // id from remote XUI
            $table->string('flow')->nullable()->default('None');
            $table->integer('limitIp')->nullable();
            $table->bigInteger('totalGb')->nullable(); // casted to GB from totalGB (bytes)
            $table->dateTime('expiryTime')->nullable();

            $table->string('tgId')->nullable();
            $table->string('subId')->nullable()->unique(); // typically unique
            $table->boolean('enable')->default(true);
            $table->integer('reset')->nullable();

            $table->text('qr_code_sub')->nullable();
            $table->text('qr_code_sub_json')->nullable();
            $table->text('qr_code_client')->nullable();
            $table->foreignId('plan_id')->nullable()->constrained('server_plans')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_clients');
    }
};
