<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-tenant Twilio subaccount columns.
 *
 * A subaccount is a Twilio construct that isolates numbers, usage
 * metrics, and spend caps for a single customer while still billing
 * under the master (Sambla) account. Twilio's "subaccount regulatory
 * inheritance" means subaccounts can purchase numbers using the
 * master's Regulatory Bundle + Address — no per-client document
 * submission required.
 *
 * These columns are NULL until the tenant's first number purchase
 * (at which point TwilioService auto-creates the subaccount). Master
 * credentials keep driving existing flows; forTenant() dispatch
 * returns a subaccount-authenticated TwilioService when the columns
 * are populated.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('telephony_subaccount_sid')->nullable()->after('settings');
            $table->text('telephony_subaccount_auth_token')->nullable()->after('telephony_subaccount_sid');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['telephony_subaccount_sid', 'telephony_subaccount_auth_token']);
        });
    }
};
