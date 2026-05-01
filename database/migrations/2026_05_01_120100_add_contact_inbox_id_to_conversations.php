<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Conversations gain a nullable contact_inbox_id reference.
 *
 * Coexists with the existing contact_id + channel_id + contact_identifier
 * trio (expand-and-contract). Once backfill completes and code paths are
 * migrated, contact_id+contact_identifier+channel_id can be considered
 * derivable from contact_inbox_id, but we do NOT drop them in this PR.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('conversations', 'contact_inbox_id')) {
            return;
        }

        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('contact_inbox_id')
                ->nullable()
                ->after('contact_id')
                ->constrained('contact_inboxes')
                ->nullOnDelete();

            $table->index('contact_inbox_id');
        });
    }

    public function down(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('drop conversations.contact_inbox_id blocked in production');
        }
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contact_inbox_id');
        });
    }
};
