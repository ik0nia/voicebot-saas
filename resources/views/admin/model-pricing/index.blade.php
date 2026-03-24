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
                <p class="text-sm text-slate-500">Costuri per model — tokens, minute, caractere</p>
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

    @php
        $unitLabels = \App\Models\ModelPricing::UNIT_LABELS;
        $providerColors = [
            'openai' => 'bg-green-100 text-green-700',
            'anthropic' => 'bg-orange-100 text-orange-700',
            'elevenlabs' => 'bg-purple-100 text-purple-700',
        ];
        $unitColors = [
            '1M_tokens' => 'bg-blue-100 text-blue-700',
            'minute' => 'bg-amber-100 text-amber-700',
            '1K_chars' => 'bg-pink-100 text-pink-700',
        ];
    @endphp

    {{-- Models Table --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Model</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Provider</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase">Unitate</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Cost Input</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Cost Output</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Context Max</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase">Activ</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Acțiuni</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($pricing as $model)
                <tr class="{{ !$model->is_active ? 'opacity-50' : '' }}">
                    <form method="POST" action="{{ route('admin.model-pricing.update', $model) }}">
                        @csrf @method('PUT')
                        <td class="px-4 py-3">
                            <span class="font-mono text-sm font-medium text-slate-900">{{ $model->model_id }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $providerColors[$model->provider] ?? 'bg-slate-100 text-slate-700' }}">
                                {{ ucfirst($model->provider) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <select name="pricing_unit" class="text-xs border border-slate-300 rounded-md px-2 py-1 focus:ring-red-500 focus:border-red-500">
                                @foreach($unitLabels as $key => $label)
                                    <option value="{{ $key }}" {{ $model->pricing_unit === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <span class="text-slate-400 text-xs">$</span>
                                <input type="number" step="0.0001" name="input_cost" value="{{ $model->input_cost }}"
                                       class="w-24 text-right text-sm border border-slate-300 rounded-md px-2 py-1 focus:ring-red-500 focus:border-red-500">
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <span class="text-slate-400 text-xs">$</span>
                                <input type="number" step="0.0001" name="output_cost" value="{{ $model->output_cost }}"
                                       class="w-24 text-right text-sm border border-slate-300 rounded-md px-2 py-1 focus:ring-red-500 focus:border-red-500">
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <input type="number" name="max_context_tokens" value="{{ $model->max_context_tokens }}"
                                   class="w-28 text-right text-sm border border-slate-300 rounded-md px-2 py-1 focus:ring-red-500 focus:border-red-500">
                        </td>
                        <td class="px-4 py-3 text-center">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" {{ $model->is_active ? 'checked' : '' }} class="sr-only peer">
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
                    <td colspan="8" class="px-4 py-8 text-center text-slate-500">Nu există modele configurate.</td>
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
                    <option value="elevenlabs">ElevenLabs</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Unitate preț</label>
                <select name="pricing_unit" class="text-sm border border-slate-300 rounded-md px-3 py-2 focus:ring-red-500 focus:border-red-500">
                    @foreach($unitLabels as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Cost Input ($)</label>
                <input type="number" step="0.0001" name="input_cost" placeholder="0.15" required
                       class="w-24 text-sm border border-slate-300 rounded-md px-3 py-2 focus:ring-red-500 focus:border-red-500">
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Cost Output ($)</label>
                <input type="number" step="0.0001" name="output_cost" placeholder="0.60" required
                       class="w-24 text-sm border border-slate-300 rounded-md px-3 py-2 focus:ring-red-500 focus:border-red-500">
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Context Max</label>
                <input type="number" name="max_context_tokens" placeholder="128000" required
                       class="w-28 text-sm border border-slate-300 rounded-md px-3 py-2 focus:ring-red-500 focus:border-red-500">
            </div>
            <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                Adaugă
            </button>
        </form>
    </div>

    {{-- Cost Estimator — calculated from real usage data when available --}}
    @php
        $prices = \App\Models\ModelPricing::where('is_active', true)->get()->keyBy('model_id');

        // Try to get REAL averages from ai_api_metrics (last 7 days)
        $realMetrics = \App\Models\AiApiMetric::where('created_at', '>=', now()->subDays(7))
            ->where('status', 'success')
            ->selectRaw("
                model,
                COUNT(*) as total_calls,
                AVG(input_tokens) as avg_input_tokens,
                AVG(output_tokens) as avg_output_tokens,
                AVG(cost_cents) as avg_cost_cents,
                AVG(response_time_ms) as avg_response_ms
            ")
            ->groupBy('model')
            ->get()
            ->keyBy('model');

        $hasRealData = $realMetrics->isNotEmpty();

        // Real chat stats: average conversation from conversations table
        $realChatStats = \Illuminate\Support\Facades\DB::selectOne("
            SELECT
                AVG(messages_count) as avg_messages,
                AVG(cost_cents) as avg_cost_cents,
                COUNT(*) as total_conversations
            FROM conversations
            WHERE created_at >= ?
              AND messages_count > 0
              AND channel_id IS NOT NULL
        ", [now()->subDays(7)]);

        // Real voice stats: average call from calls table
        $realVoiceStats = \Illuminate\Support\Facades\DB::selectOne("
            SELECT
                AVG(duration_seconds) as avg_duration,
                AVG(cost_cents) as avg_cost_cents,
                COUNT(*) as total_calls
            FROM calls
            WHERE created_at >= ?
              AND status IN ('completed', 'abandoned')
              AND duration_seconds > 0
        ", [now()->subDays(7)]);

        // Helper to get price from DB
        $getPrice = function(string $modelId, string $field = 'input_cost') use ($prices) {
            return $prices->has($modelId) ? $prices[$modelId]->{$field} : 0;
        };

        // === VOCE FĂRĂ CLONARE ===
        if ($realVoiceStats && $realVoiceStats->total_calls > 0) {
            // REAL: use actual average cost per minute from DB
            $avgCallDuration = max(1, $realVoiceStats->avg_duration);
            $voiceTotal = ($realVoiceStats->avg_cost_cents / 100) / ($avgCallDuration / 60);
            $voiceSource = 'real';
            $voiceCalls = $realVoiceStats->total_calls;
            $voiceAvgDuration = round($avgCallDuration / 60, 1);
        } else {
            // ESTIMATED: calculate from pricing table
            $voiceAudioIn = $getPrice('gpt-4o-realtime-preview-audio', 'input_cost');
            $voiceAudioOut = $getPrice('gpt-4o-realtime-preview-audio', 'output_cost');
            $voiceTextIn = (200 / 1_000_000) * $getPrice('gpt-4o-realtime-preview', 'input_cost');
            $voiceTextOut = (100 / 1_000_000) * $getPrice('gpt-4o-realtime-preview', 'output_cost');
            $voiceWhisper = $getPrice('whisper-1', 'input_cost');
            $voiceEmbed = (500 / 1_000_000) * $getPrice('text-embedding-3-small', 'input_cost');
            $voiceTotal = $voiceAudioIn + $voiceAudioOut + $voiceTextIn + $voiceTextOut + $voiceWhisper + $voiceEmbed;
            $voiceSource = 'estimated';
            $voiceCalls = 0;
            $voiceAvgDuration = 0;
        }

        // Cloned voice: estimate ElevenLabs surcharge per minute
        $voiceELCost = (150 / 1000) * $getPrice('eleven_multilingual_v2', 'input_cost');
        // Cloned replaces audio out ($0.24) with text out + ElevenLabs
        $voiceClonedSurcharge = $voiceELCost - $getPrice('gpt-4o-realtime-preview-audio', 'output_cost');
        $voiceClonedTotal = $voiceTotal + max(0, $voiceClonedSurcharge);

        // === CONVERSAȚIE CHAT ===
        if ($realChatStats && $realChatStats->total_conversations > 5) {
            // REAL: use actual average cost per conversation
            $chatTotal = ($realChatStats->avg_cost_cents ?? 0) / 100;
            $chatSource = 'real';
            $chatCount = $realChatStats->total_conversations;
            $chatAvgMsgs = round($realChatStats->avg_messages, 1);
        } else {
            // ESTIMATED: 8 messages (3x fast + 1x smart + 4x embeddings)
            $chatFastIn = (2000 / 1_000_000) * $getPrice('gpt-4o-mini', 'input_cost') * 3;
            $chatFastOut = (200 / 1_000_000) * $getPrice('gpt-4o-mini', 'output_cost') * 3;
            $chatSmartIn = (3000 / 1_000_000) * $getPrice('claude-sonnet-4-5-20241022', 'input_cost') * 1;
            $chatSmartOut = (300 / 1_000_000) * $getPrice('claude-sonnet-4-5-20241022', 'output_cost') * 1;
            $chatEmbed = (500 / 1_000_000) * $getPrice('text-embedding-3-small', 'input_cost') * 4;
            $chatTotal = $chatFastIn + $chatFastOut + $chatSmartIn + $chatSmartOut + $chatEmbed;
            $chatSource = 'estimated';
            $chatCount = 0;
            $chatAvgMsgs = 8;
        }

        // Per-model real usage breakdown for chat
        $chatModelBreakdown = $realMetrics->filter(fn($m) => in_array($m->model, ['gpt-4o-mini', 'gpt-4o', 'claude-sonnet-4-5-20241022', 'claude-haiku-4-5-20251001']));
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        {{-- Voice without cloning --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="flex items-center gap-2 mb-3">
                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                </div>
                <h3 class="text-sm font-bold text-slate-900">1 minut voce</h3>
                <span class="text-xs text-slate-400">(fără clonare)</span>
            </div>
            @if($voiceSource === 'real')
                <div class="mb-3 px-2 py-1.5 rounded-md bg-blue-50 border border-blue-200">
                    <p class="text-xs text-blue-700 font-medium">Calculat din date reale ({{ $voiceCalls }} apeluri, ultimele 7 zile)</p>
                    <p class="text-xs text-blue-500">Durata medie: {{ $voiceAvgDuration }} min</p>
                </div>
            @else
                <div class="mb-3 px-2 py-1.5 rounded-md bg-amber-50 border border-amber-200">
                    <p class="text-xs text-amber-700">Estimat din prețuri (fără date reale încă)</p>
                </div>
            @endif
            <div class="pt-2 border-t border-slate-100 flex justify-between items-baseline">
                <span class="text-sm font-semibold text-slate-900">Cost / minut</span>
                <span class="text-lg font-bold text-blue-600">${{ number_format($voiceTotal, 3) }}</span>
            </div>
            <div class="mt-2 space-y-1 text-xs text-slate-500">
                <div class="flex justify-between"><span>Apel 3 min</span><span class="font-mono font-medium text-slate-700">${{ number_format($voiceTotal * 3, 2) }}</span></div>
                <div class="flex justify-between"><span>Apel 5 min</span><span class="font-mono font-medium text-slate-700">${{ number_format($voiceTotal * 5, 2) }}</span></div>
                <div class="flex justify-between"><span>Apel 10 min</span><span class="font-mono font-medium text-slate-700">${{ number_format($voiceTotal * 10, 2) }}</span></div>
                <div class="flex justify-between border-t border-slate-100 pt-1"><span>100 apeluri/lună (5 min)</span><span class="font-mono font-semibold text-slate-900">${{ number_format($voiceTotal * 5 * 100, 0) }}</span></div>
            </div>
        </div>

        {{-- Voice with cloning --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="flex items-center gap-2 mb-3">
                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg>
                </div>
                <h3 class="text-sm font-bold text-slate-900">1 minut voce</h3>
                <span class="text-xs text-purple-500">(cu ElevenLabs)</span>
            </div>
            <div class="mb-3 px-2 py-1.5 rounded-md bg-purple-50 border border-purple-200">
                <p class="text-xs text-purple-700">+${{ number_format($voiceELCost, 3) }}/min cost ElevenLabs TTS</p>
            </div>
            <div class="pt-2 border-t border-slate-100 flex justify-between items-baseline">
                <span class="text-sm font-semibold text-slate-900">Cost / minut</span>
                <span class="text-lg font-bold text-purple-600">${{ number_format($voiceClonedTotal, 3) }}</span>
            </div>
            <div class="mt-2 space-y-1 text-xs text-slate-500">
                <div class="flex justify-between"><span>Apel 3 min</span><span class="font-mono font-medium text-slate-700">${{ number_format($voiceClonedTotal * 3, 2) }}</span></div>
                <div class="flex justify-between"><span>Apel 5 min</span><span class="font-mono font-medium text-slate-700">${{ number_format($voiceClonedTotal * 5, 2) }}</span></div>
                <div class="flex justify-between"><span>Apel 10 min</span><span class="font-mono font-medium text-slate-700">${{ number_format($voiceClonedTotal * 10, 2) }}</span></div>
                <div class="flex justify-between border-t border-slate-100 pt-1"><span>100 apeluri/lună (5 min)</span><span class="font-mono font-semibold text-slate-900">${{ number_format($voiceClonedTotal * 5 * 100, 0) }}</span></div>
            </div>
        </div>

        {{-- Chat conversation --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="flex items-center gap-2 mb-3">
                <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                </div>
                <h3 class="text-sm font-bold text-slate-900">Conversație chat</h3>
            </div>
            @if($chatSource === 'real')
                <div class="mb-3 px-2 py-1.5 rounded-md bg-emerald-50 border border-emerald-200">
                    <p class="text-xs text-emerald-700 font-medium">Calculat din date reale ({{ $chatCount }} conversații, ultimele 7 zile)</p>
                    <p class="text-xs text-emerald-500">Media: {{ $chatAvgMsgs }} mesaje/conversație</p>
                </div>
            @else
                <div class="mb-3 px-2 py-1.5 rounded-md bg-amber-50 border border-amber-200">
                    <p class="text-xs text-amber-700">Estimat (~{{ $chatAvgMsgs }} mesaje, fără date reale încă)</p>
                </div>
            @endif
            @if($chatModelBreakdown->isNotEmpty())
                <div class="space-y-1.5 text-xs text-slate-600 mb-3">
                    @foreach($chatModelBreakdown as $m)
                    <div class="flex justify-between">
                        <span>{{ $m->model }} <span class="text-slate-400">({{ number_format($m->total_calls) }}x)</span></span>
                        <span class="font-mono">avg ${{ number_format($m->avg_cost_cents / 100, 4) }}</span>
                    </div>
                    @endforeach
                </div>
            @endif
            <div class="pt-2 border-t border-slate-100 flex justify-between items-baseline">
                <span class="text-sm font-semibold text-slate-900">Cost / conversație</span>
                <span class="text-lg font-bold text-emerald-600">${{ number_format($chatTotal, 4) }}</span>
            </div>
            <div class="mt-2 space-y-1 text-xs text-slate-500">
                <div class="flex justify-between"><span>50 conv/zi</span><span class="font-mono font-medium text-slate-700">${{ number_format($chatTotal * 50, 2) }}/zi</span></div>
                <div class="flex justify-between"><span>200 conv/zi</span><span class="font-mono font-medium text-slate-700">${{ number_format($chatTotal * 200, 2) }}/zi</span></div>
                <div class="flex justify-between border-t border-slate-100 pt-1"><span>1000 conv/lună</span><span class="font-mono font-semibold text-slate-900">${{ number_format($chatTotal * 1000, 2) }}</span></div>
            </div>
        </div>
    </div>

    <p class="text-xs text-slate-400 italic">
        @if($hasRealData || ($realChatStats && $realChatStats->total_conversations > 5) || ($realVoiceStats && $realVoiceStats->total_calls > 0))
            * Cardurile cu badge <span class="text-emerald-600 font-medium">verde</span> se calculează din date reale (ultimele 7 zile). Cele cu badge <span class="text-amber-600 font-medium">galben</span> sunt estimări din prețuri. Pe măsură ce se acumulează date, estimările se înlocuiesc automat cu valori reale.
        @else
            * Estimările se calculează din prețurile de mai sus. Odată ce platforma acumulează date (apeluri, conversații), valorile se vor calcula automat din costuri reale.
        @endif
    </p>

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
                    <td class="px-4 py-2 text-sm text-right font-medium">${{ number_format(($m->total_cost ?? 0) / 100, 2) }}</td>
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
