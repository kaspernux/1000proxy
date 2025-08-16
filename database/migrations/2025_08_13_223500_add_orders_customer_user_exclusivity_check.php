<?php

use Illuminate\Database\Migrations\Migration;

/**
 * No-op migration placeholder for historical exclusivity check between orders.customer_id and orders.user_id.
 * We enforce this constraint at the application layer; database-level CHECKs are not portable across our DBs.
 */
return new class extends Migration {
	public function up(): void {}
	public function down(): void {}
};

