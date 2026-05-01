<?php

declare(strict_types=1);

namespace App\Services\Mcp;

use App\Models\TenantMcpServer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Minimal Model Context Protocol client.
 *
 * Implements the JSON-RPC 2.0 surface that all MCP servers expose:
 *  - initialize: handshake (capabilities, protocol version)
 *  - tools/list: enumerate available tools with their JSON-Schema input shapes
 *  - tools/call: invoke a tool by name with arguments
 *
 * Out of scope for this implementation:
 *  - resources/* (most chat agents don't need filesystem-style resources)
 *  - prompts/* (tenants can manage prompt templates inside Sambla, no
 *    need to fetch from MCP servers)
 *  - stdio + sse transports (HTTP-only for now; see TenantMcpServer note)
 *  - notifications (server-pushed events; needs sse first)
 *
 * Per Tiledesk's pattern, scope is per-tenant: every call carries the
 * tenant's MCP server credentials, never a shared service token.
 */
class MCPClientService
{
    private const PROTOCOL_VERSION = '2024-11-05';
    private const HTTP_TIMEOUT = 15;

    public function listTools(TenantMcpServer $server): array
    {
        if (!empty($server->tools_cache) && $server->tools_cached_at?->gt(now()->subHour())) {
            return $server->tools_cache;
        }

        $response = $this->call($server, 'tools/list', new \stdClass());
        if (!$response['success']) {
            return [];
        }

        $tools = $response['result']['tools'] ?? [];
        $server->update([
            'tools_cache' => $tools,
            'tools_cached_at' => now(),
        ]);

        return $tools;
    }

    public function callTool(TenantMcpServer $server, string $toolName, array $arguments): array
    {
        return $this->call($server, 'tools/call', [
            'name' => $toolName,
            'arguments' => $arguments,
        ]);
    }

    public function ping(TenantMcpServer $server): bool
    {
        $response = $this->call($server, 'initialize', [
            'protocolVersion' => self::PROTOCOL_VERSION,
            'capabilities' => ['tools' => new \stdClass()],
            'clientInfo' => ['name' => 'sambla', 'version' => '1.0'],
        ]);

        $status = $response['success'] ? 'ok' : ($response['error_code'] === 'unauthorized' ? 'auth_failed' : 'unreachable');
        $server->update([
            'last_health_check_at' => now(),
            'last_health_status' => $status,
        ]);

        return $response['success'];
    }

    /**
     * @return array{success: bool, result: ?array, error: ?string, error_code: ?string}
     */
    private function call(TenantMcpServer $server, string $method, mixed $params): array
    {
        if ($server->transport !== 'http') {
            return [
                'success' => false,
                'result' => null,
                'error' => "Transport '{$server->transport}' not supported by current MCPClientService",
                'error_code' => 'unsupported_transport',
            ];
        }

        $rpcId = bin2hex(random_bytes(8));
        $payload = [
            'jsonrpc' => '2.0',
            'id' => $rpcId,
            'method' => $method,
            'params' => $params,
        ];

        try {
            $request = Http::timeout(self::HTTP_TIMEOUT)
                ->withHeaders(array_filter([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => $server->authHeader(),
                ]));

            $response = $request->post($server->url, $payload);

            if ($response->status() === 401 || $response->status() === 403) {
                return [
                    'success' => false,
                    'result' => null,
                    'error' => 'MCP server rejected credentials',
                    'error_code' => 'unauthorized',
                ];
            }

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'result' => null,
                    'error' => 'MCP server returned HTTP ' . $response->status(),
                    'error_code' => 'http_' . $response->status(),
                ];
            }

            $body = $response->json();
            if (isset($body['error'])) {
                Log::warning('MCPClientService: server-side JSON-RPC error', [
                    'server_id' => $server->id,
                    'method' => $method,
                    'error' => $body['error'],
                ]);
                return [
                    'success' => false,
                    'result' => null,
                    'error' => $body['error']['message'] ?? 'JSON-RPC error',
                    'error_code' => 'rpc_' . ($body['error']['code'] ?? 'unknown'),
                ];
            }

            return [
                'success' => true,
                'result' => $body['result'] ?? [],
                'error' => null,
                'error_code' => null,
            ];
        } catch (\Throwable $e) {
            Log::error('MCPClientService: transport failure', [
                'server_id' => $server->id,
                'method' => $method,
                'exception' => $e::class,
            ]);
            return [
                'success' => false,
                'result' => null,
                'error' => $e::class,
                'error_code' => 'transport_failure',
            ];
        }
    }
}
