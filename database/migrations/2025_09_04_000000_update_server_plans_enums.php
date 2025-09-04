<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        // This migration targets MySQL-compatible engines. For other drivers, it will no-op.
        if ($driver !== 'mysql' && $driver !== 'mariadb') {
            return;
        }

        // New enum definitions matching repository source
        $protocols = "'vless','vmess','trojan','shadowsocks','dokodemo-door','socks','http','wireguard','mixed'";
        $types = "'single','multiple','shared','branded','reseller','dedicated'";

        // Use raw statements to alter enum columns safely
        DB::statement("ALTER TABLE server_plans MODIFY COLUMN protocol ENUM($protocols) NOT NULL DEFAULT 'vless'");
        DB::statement("ALTER TABLE server_plans MODIFY COLUMN `type` ENUM($types) NOT NULL DEFAULT 'multiple'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver !== 'mysql' && $driver !== 'mariadb') {
            return;
        }

        // Revert to a conservative set (original migration baseline)
        $protocols = "'vless','vmess','trojan','shadowsocks','dokodemo-door','socks','http','wireguard','mixed'";
        $types = "'single','multiple','branded','reseller','dedicated'";

        DB::statement("ALTER TABLE server_plans MODIFY COLUMN protocol ENUM($protocols) NOT NULL DEFAULT 'vless'");
        DB::statement("ALTER TABLE server_plans MODIFY COLUMN `type` ENUM($types) NOT NULL DEFAULT 'multiple'");
    }
};
