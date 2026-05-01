<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * HITL assignment columns (Etapa 4 of the omnichannel roadmap).
 *
 * A conversation is at any moment owned by EITHER a bot OR a human user,
 * never both, never neither. Modeled as two nullable FK columns + a
 * Postgres CHECK constraint enforcing the XOR. The bot-only state is the
 * default for new conversations (assignee_bot_id stamped from the channel's
 * bot at create time, in a follow-up); the human-only state means a
 * tenant_admin / tenant_manager has clicked "Preluat de operator".
 *
 * assigned_at + assigned_by_user_id are audit columns — useful for SLA
 * dashboards (who took over what, when) and for restoring the previous
 * bot when handing back.
 *
 * Additive only. The CHECK constraint is added with a name we can DROP
 * later via a separate human-approved migration if its semantics ever
 * loosen. The check ALLOWS both columns null (transitional state during
 * the data backfill window — once existing rows are stamped with their
 * channel's bot, this should never happen in practice).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            if (!Schema::hasColumn('conversations', 'assignee_user_id')) {
                // No `->after()` because column ordering depends on whether
                // E1 (contact_inbox_id) deployed first — Postgres doesn't
                // care about position for storage, just for `SELECT *`.
                $table->foreignId('assignee_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->index('assignee_user_id');
            }
            if (!Schema::hasColumn('conversations', 'assignee_bot_id')) {
                $table->foreignId('assignee_bot_id')
                    ->nullable()
                    ->constrained('bots')
                    ->nullOnDelete();
                $table->index('assignee_bot_id');
            }
            if (!Schema::hasColumn('conversations', 'assigned_at')) {
                $table->timestamp('assigned_at')->nullable();
            }
            if (!Schema::hasColumn('conversations', 'assigned_by_user_id')) {
                $table->foreignId('assigned_by_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        // XOR constraint at DB level. Postgres-specific syntax; portable to
        // MySQL 8.0.16+ via `CHECK ((cond))`. Allows both-null (transitional)
        // but blocks both-set (the actual invariant we care about).
        DB::statement(<<<SQL
            ALTER TABLE conversations
            ADD CONSTRAINT conversations_assignee_xor
            CHECK (NOT (assignee_user_id IS NOT NULL AND assignee_bot_id IS NOT NULL))
        SQL);
    }

    public function down(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('drop conversations.assignee_* columns blocked in production');
        }
        DB::statement('ALTER TABLE conversations DROP CONSTRAINT IF EXISTS conversations_assignee_xor');
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assignee_user_id');
            $table->dropConstrainedForeignId('assignee_bot_id');
            $table->dropColumn('assigned_at');
            $table->dropConstrainedForeignId('assigned_by_user_id');
        });
    }
};
