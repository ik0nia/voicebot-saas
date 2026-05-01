<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-tenant MCP (Model Context Protocol) server registry.
 *
 * Etapa 5 of the omnichannel roadmap. Tenants point at their own MCP
 * servers (CRM tool servers, custom data lookups, integration shims) and
 * the orchestrator surfaces those tools to the LLM at conversation time.
 *
 * The transport column is intentionally a string, not an enum: MCP is
 * young (1.0 spec finalized 2024) and new transports keep landing
 * (SSE was added after the initial HTTP/JSON-RPC draft). String + an
 * application-level allowlist gives us room to add 'sse' / 'stdio' /
 * 'websocket' without a migration.
 *
 * credentials is encrypted:array — same pattern as channels.credentials
 * — because tenant MCP servers usually need a bearer token or API key.
 *
 * Additive only.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tenant_mcp_servers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('url', 2048);
            $table->string('transport', 32)->default('http'); // 'http' (JSON-RPC over POST) / 'sse' / 'stdio'
            $table->text('credentials')->nullable(); // encrypted:array
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_health_check_at')->nullable();
            $table->string('last_health_status', 32)->nullable(); // 'ok' / 'unreachable' / 'auth_failed' / etc.
            // Tool catalog cache: list of tool names + schemas pulled from
            // the server's tools/list response. Refreshed by a scheduled
            // job; cached here so we don't hit the server on every send.
            $table->json('tools_cache')->nullable();
            $table->timestamp('tools_cached_at')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('tenant_mcp_servers drop blocked in production');
        }
        Schema::dropIfExists('tenant_mcp_servers');
    }
};
