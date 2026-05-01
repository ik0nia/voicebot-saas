<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: add nullable tenant_id (so existing rows survive backfill)
        Schema::table('channels', function (Blueprint $table) {
            $table->foreignId('tenant_id')
                ->nullable()
                ->after('id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->index('tenant_id');
        });

        // Step 2: backfill from bots.tenant_id (Postgres syntax)
        DB::statement('UPDATE channels SET tenant_id = bots.tenant_id FROM bots WHERE channels.bot_id = bots.id AND channels.tenant_id IS NULL');

        // Step 3: enforce NOT NULL after backfill
        Schema::table('channels', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
    }
};
