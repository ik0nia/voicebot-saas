<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop telnyx_order_id from phone_numbers — Telnyx was removed as a
 * provider on 2026-05-04. The column was nullable and held the order
 * id from Telnyx's async number provisioning flow; with Telnyx gone
 * (verified: 0 rows had it set on prod), the column is dead weight.
 *
 * Reversal: re-add as nullable string. Data is unrecoverable but
 * irrelevant since Telnyx orders are dormant.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('phone_numbers', function (Blueprint $table) {
            if (Schema::hasColumn('phone_numbers', 'telnyx_order_id')) {
                $table->dropColumn('telnyx_order_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('phone_numbers', function (Blueprint $table) {
            if (!Schema::hasColumn('phone_numbers', 'telnyx_order_id')) {
                $table->string('telnyx_order_id')->nullable()->after('status');
            }
        });
    }
};
