@extends('layouts.dashboard')

@section('title', 'Servere MCP')

@section('breadcrumb')
    <span class="text-slate-400">/</span>
    <span class="font-medium text-slate-700">Servere MCP</span>
@endsection

@section('content')
    @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-6 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            <ul class="space-y-1">@foreach($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Servere MCP</h1>
        <p class="text-sm text-slate-500 mt-1 max-w-2xl">
            Conectează agenții AI Sambla la propriile tale servere de tooluri (CRM, ERP, integrări custom) prin
            <a href="https://modelcontextprotocol.io" target="_blank" class="underline">Model Context Protocol</a>.
            Toolurile devin disponibile automat în conversații, fără cod la noi pentru fiecare integrare.
        </p>
    </div>

    @if($servers->isEmpty())
        <div class="rounded-xl border-2 border-dashed border-slate-200 px-8 py-12 text-center mb-8">
            <h2 class="text-base font-semibold text-slate-700">Niciun server MCP încă</h2>
            <p class="text-sm text-slate-500 mt-2 max-w-md mx-auto">
                Adaugă primul server pentru a expune toolurile tale agentului AI.
            </p>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm mb-8">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Nume</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">URL</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Transport</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Tooluri</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Acțiuni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($servers as $server)
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $server->name }}</td>
                            <td class="px-4 py-3 text-xs font-mono text-slate-500 truncate max-w-xs">{{ $server->url }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700">{{ $server->transport }}</td>
                            <td class="px-4 py-3 text-sm">
                                @php
                                    $statusColor = match($server->last_health_status) {
                                        'ok' => 'bg-green-50 text-green-700',
                                        'auth_failed' => 'bg-amber-50 text-amber-700',
                                        null => 'bg-slate-100 text-slate-500',
                                        default => 'bg-red-50 text-red-700',
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusColor }}">
                                    {{ $server->last_health_status ?? 'necunoscut' }}
                                </span>
                                @if($server->last_health_check_at)
                                    <p class="text-xs text-slate-400 mt-0.5">{{ $server->last_health_check_at->diffForHumans() }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ count($server->tools_cache ?? []) }}</td>
                            <td class="px-4 py-3 text-sm text-right">
                                <form method="POST" action="{{ route('dashboard.mcp-servers.ping', $server) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-slate-500 hover:text-slate-700 text-xs mr-3">Re-check</button>
                                </form>
                                <form method="POST" action="{{ route('dashboard.mcp-servers.destroy', $server) }}" class="inline"
                                      onsubmit="return confirm('Șterge serverul {{ $server->name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-xs">Șterge</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="rounded-xl border border-slate-200 bg-white p-6">
        <h2 class="text-base font-semibold text-slate-900 mb-4">Adaugă server MCP</h2>
        <form method="POST" action="{{ route('dashboard.mcp-servers.store') }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nume</label>
                    <input type="text" name="name" required maxlength="120" value="{{ old('name') }}"
                           placeholder="Ex: CRM Vânzări"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">URL endpoint</label>
                    <input type="url" name="url" required maxlength="2048" value="{{ old('url') }}"
                           placeholder="https://mcp.exemplu.com/jsonrpc"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Transport</label>
                    <select name="transport" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="http" {{ old('transport', 'http') === 'http' ? 'selected' : '' }}>HTTP (JSON-RPC)</option>
                        <option value="sse" {{ old('transport') === 'sse' ? 'selected' : '' }}>SSE (în curând)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Autentificare</label>
                    <select name="auth_type" id="auth_type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                            onchange="document.getElementById('auth_bearer').classList.toggle('hidden', this.value !== 'bearer'); document.getElementById('auth_basic').classList.toggle('hidden', this.value !== 'basic');">
                        <option value="none">Fără</option>
                        <option value="bearer">Bearer Token</option>
                        <option value="basic">Basic Auth</option>
                    </select>
                </div>
                <div id="auth_bearer" class="hidden md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Token</label>
                    <input type="password" name="token" maxlength="2048"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono">
                </div>
                <div id="auth_basic" class="hidden md:col-span-2 grid grid-cols-2 gap-2">
                    <input type="text" name="username" placeholder="Username" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <input type="password" name="password" placeholder="Password" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="rounded-lg bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                    Adaugă + verifică
                </button>
            </div>
        </form>
    </div>
@endsection
