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

    {{-- Cost Estimator --}}
    @php
        // Fetch prices from DB for calculations
        $prices = \App\Models\ModelPricing::where('is_active', true)->pluck('input_cost', 'model_id')->toArray();
        $pricesOut = \App\Models\ModelPricing::where('is_active', true)->pluck('output_cost', 'model_id')->toArray();

        // === 1 MINUT VOCE (fără voice cloning) ===
        // Audio in: 1 min Realtime audio input
        $voiceAudioIn = ($prices['gpt-4o-realtime-preview-audio'] ?? 0.06);
        // Audio out: 1 min Realtime audio output
        $voiceAudioOut = ($pricesOut['gpt-4o-realtime-preview-audio'] ?? 0.24);
        // Text tokens: ~200 input + 100 output tokens per minute exchange
        $voiceTextIn = (200 / 1_000_000) * ($prices['gpt-4o-realtime-preview'] ?? 5.00);
        $voiceTextOut = (100 / 1_000_000) * ($pricesOut['gpt-4o-realtime-preview'] ?? 20.00);
        // Whisper transcription: 1 min
        $voiceWhisper = ($prices['whisper-1'] ?? 0.006);
        // Embedding for knowledge context: ~1 query
        $voiceEmbed = (500 / 1_000_000) * ($prices['text-embedding-3-small'] ?? 0.02);

        $voiceTotal = $voiceAudioIn + $voiceAudioOut + $voiceTextIn + $voiceTextOut + $voiceWhisper + $voiceEmbed;

        // === 1 MINUT VOCE (cu voice cloning ElevenLabs) ===
        // Audio in same, but output goes through ElevenLabs instead of OpenAI audio out
        // ~150 chars output per minute of speech
        $voiceELCost = (150 / 1000) * ($prices['eleven_multilingual_v2'] ?? 0.30);
        // Text out instead of audio out (cheaper)
        $voiceTextOutOnly = (100 / 1_000_000) * ($pricesOut['gpt-4o-realtime-preview'] ?? 20.00);
        $voiceClonedTotal = $voiceAudioIn + $voiceTextOutOnly + $voiceTextIn + $voiceWhisper + $voiceEmbed + $voiceELCost;

        // === CONVERSAȚIE CHAT MEDIE ===
        // Asumăm: 8 mesaje (4 user + 4 bot), ~100 words user, ~150 words bot per msg
        // Tier fast (gpt-4o-mini): 3 mesaje, Tier smart (claude-sonnet): 1 mesaj
        // Input avg: ~2000 tokens (system + history + knowledge), Output avg: ~200 tokens
        $chatFastIn = (2000 / 1_000_000) * ($prices['gpt-4o-mini'] ?? 0.15) * 3;
        $chatFastOut = (200 / 1_000_000) * ($pricesOut['gpt-4o-mini'] ?? 0.60) * 3;
        $chatSmartIn = (3000 / 1_000_000) * ($prices['claude-sonnet-4-5-20241022'] ?? 3.00) * 1;
        $chatSmartOut = (300 / 1_000_000) * ($pricesOut['claude-sonnet-4-5-20241022'] ?? 15.00) * 1;
        // Embeddings: 4 knowledge searches
        $chatEmbed = (500 / 1_000_000) * ($prices['text-embedding-3-small'] ?? 0.02) * 4;

        $chatTotal = $chatFastIn + $chatFastOut + $chatSmartIn + $chatSmartOut + $chatEmbed;
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
            <div class="space-y-1.5 text-xs text-slate-600 mb-3">
                <div class="flex justify-between"><span>Realtime audio in</span><span class="font-mono">${{ number_format($voiceAudioIn, 4) }}</span></div>
                <div class="flex justify-between"><span>Realtime audio out</span><span class="font-mono">${{ number_format($voiceAudioOut, 4) }}</span></div>
                <div class="flex justify-between"><span>Realtime text tokens</span><span class="font-mono">${{ number_format($voiceTextIn + $voiceTextOut, 4) }}</span></div>
                <div class="flex justify-between"><span>Whisper transcriere</span><span class="font-mono">${{ number_format($voiceWhisper, 4) }}</span></div>
                <div class="flex justify-between"><span>Embedding (knowledge)</span><span class="font-mono">${{ number_format($voiceEmbed, 4) }}</span></div>
            </div>
            <div class="pt-2 border-t border-slate-100 flex justify-between items-baseline">
                <span class="text-sm font-semibold text-slate-900">Total / minut</span>
                <span class="text-lg font-bold text-blue-600">${{ number_format($voiceTotal, 3) }}</span>
            </div>
            <p class="text-xs text-slate-400 mt-1">Apel 5 min ≈ ${{ number_format($voiceTotal * 5, 2) }} | 10 min ≈ ${{ number_format($voiceTotal * 10, 2) }}</p>
        </div>

        {{-- Voice with cloning --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="flex items-center gap-2 mb-3">
                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg>
                </div>
                <h3 class="text-sm font-bold text-slate-900">1 minut voce</h3>
                <span class="text-xs text-purple-500">(cu clonare ElevenLabs)</span>
            </div>
            <div class="space-y-1.5 text-xs text-slate-600 mb-3">
                <div class="flex justify-between"><span>Realtime audio in</span><span class="font-mono">${{ number_format($voiceAudioIn, 4) }}</span></div>
                <div class="flex justify-between"><span>Realtime text out</span><span class="font-mono">${{ number_format($voiceTextOutOnly, 4) }}</span></div>
                <div class="flex justify-between"><span>Realtime text tokens in</span><span class="font-mono">${{ number_format($voiceTextIn, 4) }}</span></div>
                <div class="flex justify-between"><span>Whisper transcriere</span><span class="font-mono">${{ number_format($voiceWhisper, 4) }}</span></div>
                <div class="flex justify-between"><span>ElevenLabs TTS (~150 chars)</span><span class="font-mono text-purple-600">${{ number_format($voiceELCost, 4) }}</span></div>
                <div class="flex justify-between"><span>Embedding (knowledge)</span><span class="font-mono">${{ number_format($voiceEmbed, 4) }}</span></div>
            </div>
            <div class="pt-2 border-t border-slate-100 flex justify-between items-baseline">
                <span class="text-sm font-semibold text-slate-900">Total / minut</span>
                <span class="text-lg font-bold text-purple-600">${{ number_format($voiceClonedTotal, 3) }}</span>
            </div>
            <p class="text-xs text-slate-400 mt-1">Apel 5 min ≈ ${{ number_format($voiceClonedTotal * 5, 2) }} | 10 min ≈ ${{ number_format($voiceClonedTotal * 10, 2) }}</p>
        </div>

        {{-- Chat conversation --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="flex items-center gap-2 mb-3">
                <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                </div>
                <h3 class="text-sm font-bold text-slate-900">Conversație chat medie</h3>
                <span class="text-xs text-slate-400">(~8 mesaje)</span>
            </div>
            <div class="space-y-1.5 text-xs text-slate-600 mb-3">
                <div class="flex justify-between"><span>3x gpt-4o-mini (fast tier)</span><span class="font-mono">${{ number_format($chatFastIn + $chatFastOut, 4) }}</span></div>
                <div class="flex justify-between"><span>1x claude-sonnet (smart tier)</span><span class="font-mono">${{ number_format($chatSmartIn + $chatSmartOut, 4) }}</span></div>
                <div class="flex justify-between"><span>4x embedding (knowledge search)</span><span class="font-mono">${{ number_format($chatEmbed, 4) }}</span></div>
            </div>
            <div class="pt-2 border-t border-slate-100 flex justify-between items-baseline">
                <span class="text-sm font-semibold text-slate-900">Total / conversație</span>
                <span class="text-lg font-bold text-emerald-600">${{ number_format($chatTotal, 4) }}</span>
            </div>
            <p class="text-xs text-slate-400 mt-1">100 conv/zi ≈ ${{ number_format($chatTotal * 100, 2) }} | 1000 conv/zi ≈ ${{ number_format($chatTotal * 1000, 2) }}</p>
        </div>
    </div>

    <p class="text-xs text-slate-400 italic">* Estimările se calculează automat din prețurile de mai sus. Modifică prețurile și estimările se actualizează.</p>

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
