<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * DB-level guard against cross-tenant `phone_number_id` collisions.
     * The application-level duplicate check in ChannelController bypasses
     * the BelongsToTenant scope, but the unique partial index makes it
     * impossible to bypass even if a future controller forgets to.
     *
     * Applies only to WhatsApp channels that are still active. Soft-paused
     * channels keep their row so an attacker cannot squat a number by
     * deactivating; we just don't enforce uniqueness against the inactive
     * row, which lets a number be re-onboarded after the tenant relinquishes.
     */
    public function up(): void
    {
        DB::statement(
            "CREATE UNIQUE INDEX IF NOT EXISTS channels_whatsapp_external_id_unique "
            . "ON channels (external_id) "
            . "WHERE type = 'whatsapp' AND is_active = true AND external_id IS NOT NULL"
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS channels_whatsapp_external_id_unique');
    }
};
