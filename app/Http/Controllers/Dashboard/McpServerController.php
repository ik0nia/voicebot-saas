<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\TenantMcpServer;
use App\Services\Mcp\MCPClientService;
use Illuminate\Http\Request;

class McpServerController extends Controller
{
    public function __construct(private MCPClientService $mcp) {}

    public function index()
    {
        $servers = TenantMcpServer::query()->latest()->get();
        return view('dashboard.mcp-servers.index', compact('servers'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateServer($request);

        $server = TenantMcpServer::create([
            'name' => $validated['name'],
            'url' => $validated['url'],
            'transport' => $validated['transport'],
            'is_active' => true,
        ]);

        if (!empty($validated['auth_type']) && $validated['auth_type'] !== 'none') {
            $creds = ['auth_type' => $validated['auth_type']];
            if ($validated['auth_type'] === 'bearer' && !empty($validated['token'])) {
                $creds['token'] = $validated['token'];
            }
            if ($validated['auth_type'] === 'basic') {
                $creds['username'] = $validated['username'] ?? '';
                $creds['password'] = $validated['password'] ?? '';
            }
            $server->credentials = $creds;
            $server->save();
        }

        // Health-check + tools-cache in one shot. Errors are surfaced in
        // the index view via last_health_status; we never throw to the user.
        $this->mcp->ping($server);
        $this->mcp->listTools($server->fresh());

        return redirect()
            ->route('dashboard.mcp-servers.index')
            ->with('success', 'Server MCP adăugat.');
    }

    public function ping(TenantMcpServer $server)
    {
        $this->mcp->ping($server);
        $this->mcp->listTools($server->fresh());
        return back()->with('success', 'Health-check rulat.');
    }

    public function destroy(TenantMcpServer $server)
    {
        $server->delete();
        return redirect()
            ->route('dashboard.mcp-servers.index')
            ->with('success', 'Server MCP șters.');
    }

    private function validateServer(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:120',
            'url' => 'required|url|max:2048',
            'transport' => 'required|in:' . implode(',', TenantMcpServer::TRANSPORTS),
            'auth_type' => 'nullable|in:none,bearer,basic',
            'token' => 'nullable|string|max:2048',
            'username' => 'nullable|string|max:120',
            'password' => 'nullable|string|max:200',
        ]);
    }
}
