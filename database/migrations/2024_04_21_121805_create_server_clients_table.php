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
            $table->text('client_link')->nullable();
            $table->text('remote_sub_link')->nullable();
            $table->text('remote_json_link')->nullable();
            $table->string('tgId')->nullable();
            $table->string('subId')->nullable()->unique(); // typically unique
            $table->boolean('enable')->default(true);
            $table->integer('reset')->nullable();
            $table->text('qr_code_sub')->nullable();
            $table->text('qr_code_sub_json')->nullable();
            $table->text('qr_code_client')->nullable();
            $table->foreignId('plan_id')->nullable()->constrained('server_plans')->nullOnDelete();
            $table->string('security')->nullable();
            $table->string('pbk')->nullable();
            $table->string('fp')->nullable();
            $table->string('sni')->nullable();
            $table->string('sid')->nullable();
            $table->string('spx')->nullable();
            $table->string('grpc_service_name')->nullable();
            $table->string('network_type')->nullable();
            $table->string('tls_type')->nullable();
            $table->string('alpn')->nullable();
            $table->string('header_type')->nullable();
            $table->string('host')->nullable();
            $table->string('path')->nullable();
            $table->string('kcp_seed')->nullable();
            $table->string('kcp_type')->nullable();
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
