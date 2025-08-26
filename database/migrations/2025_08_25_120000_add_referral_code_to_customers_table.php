<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'referral_code')) {
                $table->string('referral_code', 50)->nullable()->unique()->after('refcode');
            }
        });

        // Backfill referral_code for existing customers, ensuring uniqueness
        $existing = DB::table('customers')->select('id', 'referral_code')->get();
        $used = DB::table('customers')->whereNotNull('referral_code')->pluck('referral_code')->map(fn($c) => strtoupper((string) $c))->filter()->all();
        $usedSet = array_fill_keys($used, true);

        foreach ($existing as $row) {
            if (!empty($row->referral_code)) {
                continue;
            }
            // Try up to a few times to generate a unique code
            $code = null;
            for ($i = 0; $i < 5; $i++) {
                $try = strtoupper(Str::random(8));
                if (!isset($usedSet[$try])) {
                    $code = $try;
                    $usedSet[$try] = true;
                    break;
                }
            }
            if ($code) {
                DB::table('customers')->where('id', $row->id)->update(['referral_code' => $code]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'referral_code')) {
                // Try to drop by conventional index name if it exists
                try { $table->dropUnique('customers_referral_code_unique'); } catch (\Throwable $e) {}
                $table->dropColumn('referral_code');
            }
        });
    }
};
