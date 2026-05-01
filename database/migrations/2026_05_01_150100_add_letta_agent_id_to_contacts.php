<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Letta agent ID stamped on Contact for cross-channel memory continuity.
 *
 * When a Letta sidecar is deployed (opt-in via env), the orchestrator
 * routes inbound messages through Letta for the affected Contact —
 * Letta keeps a per-Contact memory block that persists across sessions
 * and across channels (the same Maria reaching us on WA, then on FB
 * later, gets the same agent context).
 *
 * Nullable; if Letta is not deployed, this stays null and the
 * orchestrator falls through to the existing direct-LLM path.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('contacts', 'letta_agent_id')) {
            return;
        }
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('letta_agent_id', 64)->nullable();
            $table->index('letta_agent_id');
        });
    }

    public function down(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('drop contacts.letta_agent_id blocked in production');
        }
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropIndex(['letta_agent_id']);
            $table->dropColumn('letta_agent_id');
        });
    }
};
