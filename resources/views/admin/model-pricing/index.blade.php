@extends('layouts.admin')

@section('title', 'Prețuri Modele AI')
@section('breadcrumb')<span class="text-slate-900 font-medium">Prețuri Modele AI</span>@endsection

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-900">Prețuri Modele AI</h1>
                <p class="text-sm text-slate-500">Cost per 1M tokens — folosit pentru calculul costurilor în timp real</p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
            <p class="text-sm font-medium text-emerald-800">{{ session('success') }}</p>
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-4">
            <ul class="text-sm text-red-700 space-y-1">
                @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    @endif

    {{-- Existing Models --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Model</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Provider</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Input $/1M</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Output $/1M</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Context Max</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Acțiuni</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($pricing as $model)
                <tr>
                    <form method="POST" action="{{ route('admin.model-pricing.update', $model) }}">
                        @csrf @method('PUT')
                        <td class="px-4 py-3">
                            <span class="font-mono text-sm font-medium text-slate-900">{{ $model->model_id }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $model->provider === 'openai' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700' }}">
                                {{ ucfirst($model->provider) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <input type="number" step="0.0001" name="input_cost_per_million" value="{{ $model->input_cost_per_million }}"
                                   class="w-24 text-right text-sm border border-slate-300 rounded-md px-2 py-1 focus:ring-red-500 focus:border-red-500">
                        </td>
                        <td class="px-4 py-3 text-right">
                            <input type="number" step="0.0001" name="output_cost_per_million" value="{{ $model->output_cost_per_million }}"
                                   class="w-24 text-right text-sm border border-slate-300 rounded-md px-2 py-1 focus:ring-red-500 focus:border-red-500">
                        </td>
                        <td class="px-4 py-3 text-right">
                            <input type="number" name="max_context_tokens" value="{{ $model->max_context_tokens }}"
                                   class="w-28 text-right text-sm border border-slate-300 rounded-md px-2 py-1 focus:ring-red-500 focus:border-red-500">
                        </td>
                        <td class="px-4 py-3 text-center">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" {{ $model->is_active ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-9 h-5 bg-slate-200 peer-focus:ring-2 peer-focus:ring-red-300 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-red-600"></div>
                            </label>
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <button type="submit" class="text-sm text-red-600 hover:text-red-800 font-medium">Salvează</button>
                    </form>
                    <form method="POST" action="{{ route('admin.model-pricing.destroy', $model) }}" class="inline"
                          onsubmit="return confirm('Ștergi modelul {{ $model->model_id }}?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-sm text-slate-400 hover:text-red-600">Șterge</button>
                    </form>
                        </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-slate-500">Nu există modele configurate. Adaugă primul model mai jos.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Add New Model --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h3 class="text-sm font-semibold text-slate-900 mb-4">Adaugă model nou</h3>
        <form method="POST" action="{{ route('admin.model-pricing.store') }}" class="flex flex-wrap items-end gap-3">
            @csrf
            <div>
                <label class="block text-xs text-slate-500 mb-1">Model ID</label>
                <input type="text" name="model_id" placeholder="gpt-4o-mini" required
                       class="w-48 text-sm border border-slate-300 rounded-md px-3 py-2 focus:ring-red-500 focus:border-red-500">
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Provider</label>
                <select name="provider" class="text-sm border border-slate-300 rounded-md px-3 py-2 focus:ring-red-500 focus:border-red-500">
                    <option value="openai">OpenAI</option>
                    <option value="anthropic">Anthropic</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Input $/1M</label>
                <input type="number" step="0.0001" name="input_cost_per_million" placeholder="0.15" required
                       class="w-24 text-sm border border-slate-300 rounded-md px-3 py-2 focus:ring-red-500 focus:border-red-500">
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Output $/1M</label>
                <input type="number" step="0.0001" name="output_cost_per_million" placeholder="0.60" required
                       class="w-24 text-sm border border-slate-300 rounded-md px-3 py-2 focus:ring-red-500 focus:border-red-500">
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Context Tokens</label>
                <input type="number" name="max_context_tokens" placeholder="128000" required
                       class="w-28 text-sm border border-slate-300 rounded-md px-3 py-2 focus:ring-red-500 focus:border-red-500">
            </div>
            <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                Adaugă
            </button>
        </form>
    </div>

    {{-- Telemetry Summary --}}
    @php
        $metricsToday = \App\Models\AiApiMetric::whereDate('created_at', today())
            ->selectRaw('provider, model, COUNT(*) as calls, SUM(cost_cents) as total_cost, AVG(response_time_ms) as avg_response, SUM(CASE WHEN status != \'success\' THEN 1 ELSE 0 END) as errors')
            ->groupBy('provider', 'model')
            ->orderByDesc('calls')
            ->get();
    @endphp
    @if($metricsToday->isNotEmpty())
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200">
            <h3 class="text-sm font-semibold text-slate-900">Utilizare astăzi</h3>
        </div>
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-slate-600">Model</th>
                    <th class="px-4 py-2 text-right text-xs font-semibold text-slate-600">Apeluri</th>
                    <th class="px-4 py-2 text-right text-xs font-semibold text-slate-600">Cost</th>
                    <th class="px-4 py-2 text-right text-xs font-semibold text-slate-600">Timp mediu</th>
                    <th class="px-4 py-2 text-right text-xs font-semibold text-slate-600">Erori</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($metricsToday as $m)
                <tr>
                    <td class="px-4 py-2 text-sm font-mono">{{ $m->model }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ number_format($m->calls) }}</td>
                    <td class="px-4 py-2 text-sm text-right font-medium">${{ number_format($m->total_cost / 100, 2) }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ number_format($m->avg_response) }}ms</td>
                    <td class="px-4 py-2 text-sm text-right {{ $m->errors > 0 ? 'text-red-600 font-medium' : 'text-slate-400' }}">{{ $m->errors }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

</div>
@endsection
