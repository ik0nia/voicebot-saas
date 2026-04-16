<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('bot_knowledge', 'tenant_id')) {
            Schema::table('bot_knowledge', function (Blueprint $table) {
                $table->foreignId('tenant_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('tenants')
                    ->cascadeOnDelete();
            });
        }

        DB::statement(<<<'SQL'
            UPDATE bot_knowledge bk
            SET tenant_id = b.tenant_id
            FROM bots b
            WHERE bk.bot_id = b.id
              AND bk.tenant_id IS NULL
              AND b.tenant_id IS NOT NULL
        SQL);

        DB::statement('CREATE INDEX IF NOT EXISTS bot_knowledge_tenant_id_idx ON bot_knowledge (tenant_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS bot_knowledge_tenant_bot_status_idx ON bot_knowledge (tenant_id, bot_id, status)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS bot_knowledge_tenant_bot_status_idx');
        DB::statement('DROP INDEX IF EXISTS bot_knowledge_tenant_id_idx');

        if (Schema::hasColumn('bot_knowledge', 'tenant_id')) {
            Schema::table('bot_knowledge', function (Blueprint $table) {
                $table->dropConstrainedForeignId('tenant_id');
            });
        }
    }
};
