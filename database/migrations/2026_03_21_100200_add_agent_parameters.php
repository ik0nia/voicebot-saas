<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('knowledge_agents', function (Blueprint $table) {
            if (!Schema::hasColumn('knowledge_agents', 'system_prompt')) {
                $table->text('system_prompt')->nullable()->after('default_prompt');
            }
            if (!Schema::hasColumn('knowledge_agents', 'temperature')) {
                $table->float('temperature')->default(0.7)->after('system_prompt');
            }
            if (!Schema::hasColumn('knowledge_agents', 'max_tokens')) {
                $table->unsignedInteger('max_tokens')->default(4000)->after('temperature');
            }
            if (!Schema::hasColumn('knowledge_agents', 'model')) {
                $table->string('model')->default('gpt-4o-mini')->after('max_tokens');
            }
        });

        Schema::table('knowledge_agent_runs', function (Blueprint $table) {
            if (!Schema::hasColumn('knowledge_agent_runs', 'model_used')) {
                $table->string('model_used')->nullable()->after('tokens_used');
            }
            if (!Schema::hasColumn('knowledge_agent_runs', 'metadata')) {
                $table->json('metadata')->nullable()->after('model_used');
            }
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_agents', function (Blueprint $table) {
            $columns = ['system_prompt', 'temperature', 'max_tokens'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('knowledge_agents', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('knowledge_agent_runs', function (Blueprint $table) {
            $columns = array_filter(['model_used', 'metadata'], fn($col) => Schema::hasColumn('knowledge_agent_runs', $col));
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
