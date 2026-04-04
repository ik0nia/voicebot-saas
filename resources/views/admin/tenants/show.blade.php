@extends('layouts.admin')
@section('title', $tenant->name . ' - Admin')
@section('content')
<div class="max-w-[1200px] space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.tenants.index') }}" class="text-slate-400 hover:text-slate-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg></a>
            <h1 class="text-2xl font-bold text-slate-900">{{ $tenant->name }}</h1>
            <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $tenant->plan_slug === 'free' ? 'bg-slate-100 text-slate-600' : 'bg-blue-100 text-blue-700' }}">{{ ucfirst($tenant->plan_slug ?? 'free') }}</span>
        </div>
        <div class="text-xs text-slate-400">ID: {{ $tenant->id }} · Creat: {{ $tenant->created_at->format('d.m.Y') }}</div>
    </div>

    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 text-sm text-emerald-800">✓ {{ session('success') }}</div>
    @endif

    {{-- Stats Row --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-[10px] text-slate-400 uppercase">Utilizatori</p><p class="text-xl font-bold text-slate-900 mt-1">{{ count($users) }}</p></div>
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-[10px] text-slate-400 uppercase">Boți</p><p class="text-xl font-bold text-slate-900 mt-1">{{ count($bots) }}</p></div>
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-[10px] text-slate-400 uppercase">Leads</p><p class="text-xl font-bold text-violet-600 mt-1">{{ $leads }}</p></div>
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-[10px] text-slate-400 uppercase">Oportunități</p><p class="text-xl font-bold text-amber-600 mt-1">{{ $opportunities }}</p></div>
        <div class="bg-emerald-50 rounded-xl border border-emerald-200 p-4"><p class="text-[10px] text-emerald-600 uppercase">Revenue</p><p class="text-xl font-bold text-emerald-700 mt-1">{{ number_format($revenue / 100, 0) }} RON</p></div>
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-[10px] text-slate-400 uppercase">Billing</p><p class="text-xl font-bold mt-1 {{ $tenant->billing_complete ? 'text-emerald-600' : 'text-red-500' }}">{{ $tenant->billing_complete ? '✓' : '✗' }}</p></div>
    </div>

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- PLAN & OVERRIDE-URI (CRITIC) --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        {{-- Plan Change --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <h2 class="text-sm font-semibold text-slate-900 mb-4">Plan Actual</h2>
            <form method="POST" action="{{ route('admin.tenants.changePlan', $tenant) }}" class="flex items-end gap-3">
                @csrf
                <div class="flex-1">
                    <label class="text-[10px] text-slate-400 uppercase block mb-1">Plan</label>
                    <select name="plan_slug" class="w-full border-slate-300 rounded-lg text-sm">
                        @foreach($allPlans as $p)
                        <option value="{{ $p->slug }}" {{ ($tenant->plan_slug ?? 'free') === $p->slug ? 'selected' : '' }}>
                            {{ $p->name }} ({{ $p->slug }}) — {{ $p->price_monthly ? number_format($p->price_monthly, 0) . '€/lună' : 'Gratuit' }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <button class="px-4 py-2 bg-slate-900 text-white text-sm rounded-lg font-medium hover:bg-slate-800">Schimbă</button>
            </form>

            {{-- Custom pricing --}}
            <div class="mt-4 pt-4 border-t border-slate-100">
                <h3 class="text-xs font-semibold text-slate-500 uppercase mb-2">Tarif Custom</h3>
                <form method="POST" action="{{ route('admin.tenants.override', $tenant) }}" class="flex items-end gap-2">
                    @csrf
                    <div class="flex-1">
                        <label class="text-[10px] text-slate-400 block mb-1">Preț lunar (€)</label>
                        <input type="text" name="value" value="{{ $tenant->plan_overrides['custom_price_monthly'] ?? '' }}" placeholder="ex: 49.99" class="w-full border-slate-300 rounded-lg text-sm">
                        <input type="hidden" name="key" value="custom_price_monthly">
                    </div>
                    <button class="px-3 py-2 bg-slate-800 text-white text-xs rounded-lg">Setează</button>
                </form>
            </div>
        </div>

        {{-- Usage Summary --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <h2 class="text-sm font-semibold text-slate-900 mb-4">Usage Curent</h2>
            @if($usage)
            <div class="space-y-3">
                @foreach([
                    ['label' => 'Mesaje', 'used' => $usage['messages']['used'] ?? 0, 'limit' => $usage['messages']['limit'] ?? 0, 'pct' => $usage['messages']['percent'] ?? 0],
                    ['label' => 'Voice (min)', 'used' => $usage['voice_minutes']['used'] ?? 0, 'limit' => $usage['voice_minutes']['limit'] ?? 0, 'pct' => $usage['voice_minutes']['percent'] ?? 0],
                    ['label' => 'Boți', 'used' => $usage['bots']['used'] ?? 0, 'limit' => $usage['bots']['limit'] ?? 0, 'pct' => $usage['bots']['percent'] ?? 0],
                    ['label' => 'Knowledge (KB)', 'used' => $usage['knowledge']['used'] ?? 0, 'limit' => $usage['knowledge']['limit'] ?? 0, 'pct' => $usage['knowledge']['percent'] ?? 0],
                ] as $u)
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-slate-600 font-medium">{{ $u['label'] }}</span>
                        <span class="{{ $u['pct'] >= 90 ? 'text-red-600 font-semibold' : 'text-slate-500' }}">
                            {{ number_format($u['used']) }} / {{ $u['limit'] == -1 ? '∞' : number_format($u['limit']) }}
                            @if($u['pct'] > 0)({{ $u['pct'] }}%)@endif
                        </span>
                    </div>
                    <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full {{ $u['pct'] >= 90 ? 'bg-red-500' : ($u['pct'] >= 70 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                             style="width: {{ min($u['pct'], 100) }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-sm text-slate-400">Nu se poate calcula usage.</p>
            @endif
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- OVERRIDE-URI DETALIATE --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-slate-900">Override-uri Plan</h2>
            <span class="text-xs text-slate-400">Suprascriu limitele planului</span>
        </div>

        {{-- Active overrides --}}
        @if($tenant->plan_overrides && count($tenant->plan_overrides) > 0)
        <div class="mb-4">
            <table class="w-full text-sm">
                <thead><tr class="border-b border-slate-100">
                    <th class="text-left py-2 text-xs text-slate-400 font-medium">Cheie</th>
                    <th class="text-left py-2 text-xs text-slate-400 font-medium">Plan Default</th>
                    <th class="text-left py-2 text-xs text-slate-400 font-medium">Override</th>
                    <th class="py-2"></th>
                </tr></thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($tenant->plan_overrides as $key => $value)
                    <tr>
                        <td class="py-2 font-mono text-xs text-slate-700">{{ $key }}</td>
                        <td class="py-2 text-xs text-slate-400">{{ $planLimits ? ($planLimits->getLimit($key, '—')) : '—' }}</td>
                        <td class="py-2 text-sm font-semibold text-blue-600">{{ is_bool($value) ? ($value ? 'true' : 'false') : $value }}</td>
                        <td class="py-2 text-right">
                            <form method="POST" action="{{ route('admin.tenants.removeOverride', [$tenant, $key]) }}" class="inline">
                                @csrf @method('DELETE')
                                <button class="text-xs text-red-500 hover:text-red-700">Șterge</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <p class="text-xs text-slate-400 mb-4">Niciun override activ. Se folosesc limitele planului {{ ucfirst($tenant->plan_slug ?? 'free') }}.</p>
        @endif

        {{-- Add new override --}}
        <div class="border-t border-slate-100 pt-4">
            <h3 class="text-xs font-semibold text-slate-500 uppercase mb-3">Adaugă Override</h3>
            <form method="POST" action="{{ route('admin.tenants.override', $tenant) }}" class="flex items-end gap-2 flex-wrap">
                @csrf
                <div class="w-52">
                    <label class="text-[10px] text-slate-400 block mb-1">Limită</label>
                    <select name="key" class="w-full border-slate-300 rounded-lg text-xs">
                        <optgroup label="Limite Numerice">
                            <option value="max_bots">max_bots</option>
                            <option value="max_sites">max_sites</option>
                            <option value="max_knowledge_kb">max_knowledge_kb</option>
                            <option value="max_messages_per_month">max_messages_per_month</option>
                            <option value="voice_minutes_per_month">voice_minutes_per_month</option>
                            <option value="max_agents">max_agents</option>
                            <option value="max_agent_runs_per_month">max_agent_runs_per_month</option>
                            <option value="max_tokens_per_month">max_tokens_per_month</option>
                            <option value="max_scan_pages_per_month">max_scan_pages_per_month</option>
                            <option value="max_connectors">max_connectors</option>
                            <option value="max_products">max_products</option>
                        </optgroup>
                        <optgroup label="Features (true/false)">
                            <option value="custom_prompts">custom_prompts</option>
                            <option value="website_scanner">website_scanner</option>
                            <option value="export_knowledge">export_knowledge</option>
                            <option value="api_access">api_access</option>
                            <option value="priority_processing">priority_processing</option>
                            <option value="dedicated_support">dedicated_support</option>
                            <option value="white_label">white_label</option>
                            <option value="voice_enabled">voice_enabled</option>
                            <option value="lead_capture">lead_capture</option>
                            <option value="commerce_tracking">commerce_tracking</option>
                        </optgroup>
                        <optgroup label="Tarif Custom">
                            <option value="custom_price_monthly">custom_price_monthly (€)</option>
                            <option value="custom_price_annual">custom_price_annual (€)</option>
                            <option value="overage_cost_per_message">overage_cost_per_message (€)</option>
                            <option value="overage_cost_per_minute">overage_cost_per_minute (€)</option>
                            <option value="phone_number_monthly_cost_lei">phone_number_monthly_cost_lei (lei)</option>
                        </optgroup>
                    </select>
                </div>
                <div class="w-36">
                    <label class="text-[10px] text-slate-400 block mb-1">Valoare</label>
                    <input type="text" name="value" placeholder="100 / true / 49.99" class="w-full border-slate-300 rounded-lg text-sm" required>
                </div>
                <button class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg font-medium hover:bg-blue-700">Adaugă</button>
            </form>
        </div>

        {{-- Quick actions --}}
        <div class="border-t border-slate-100 pt-4 mt-4">
            <h3 class="text-xs font-semibold text-slate-500 uppercase mb-3">Acțiuni Rapide</h3>
            <div class="flex flex-wrap gap-2">
                <form method="POST" action="{{ route('admin.tenants.override', $tenant) }}" class="inline">
                    @csrf <input type="hidden" name="key" value="voice_minutes_per_month"><input type="hidden" name="value" value="100">
                    <button class="px-3 py-1.5 bg-sky-100 text-sky-700 text-xs rounded-lg font-medium hover:bg-sky-200">🎙 +100 min voice gratis</button>
                </form>
                <form method="POST" action="{{ route('admin.tenants.override', $tenant) }}" class="inline">
                    @csrf <input type="hidden" name="key" value="voice_minutes_per_month"><input type="hidden" name="value" value="0">
                    <button class="px-3 py-1.5 bg-red-100 text-red-700 text-xs rounded-lg font-medium hover:bg-red-200">🔇 Dezactivează voice</button>
                </form>
                <form method="POST" action="{{ route('admin.tenants.override', $tenant) }}" class="inline">
                    @csrf <input type="hidden" name="key" value="max_messages_per_month"><input type="hidden" name="value" value="10000">
                    <button class="px-3 py-1.5 bg-emerald-100 text-emerald-700 text-xs rounded-lg font-medium hover:bg-emerald-200">💬 +10K mesaje</button>
                </form>
                <form method="POST" action="{{ route('admin.tenants.override', $tenant) }}" class="inline">
                    @csrf <input type="hidden" name="key" value="api_access"><input type="hidden" name="value" value="true">
                    <button class="px-3 py-1.5 bg-purple-100 text-purple-700 text-xs rounded-lg font-medium hover:bg-purple-200">🔑 Activează API</button>
                </form>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- COMPANY DATA --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <h2 class="text-sm font-semibold text-slate-900 mb-4">Date Companie</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
            @foreach([
                'Nume' => $tenant->company_name,
                'CIF' => $tenant->company_cif,
                'Nr. Reg.' => $tenant->company_reg_number,
                'Email' => $tenant->company_email,
                'Telefon' => $tenant->company_phone,
                'Contact' => $tenant->company_contact_person,
                'Oraș' => $tenant->company_city,
                'Județ' => $tenant->company_county,
                'IBAN' => $tenant->company_iban,
                'Bancă' => $tenant->company_bank,
            ] as $label => $val)
            <div>
                <p class="text-[10px] text-slate-400 uppercase">{{ $label }}</p>
                <p class="text-slate-700 {{ $val ? '' : 'text-red-400 italic' }}">{{ $val ?: 'Lipsă' }}</p>
            </div>
            @endforeach
        </div>
        @if($tenant->company_address)
        <div class="mt-2"><p class="text-[10px] text-slate-400 uppercase">Adresă</p><p class="text-sm text-slate-700">{{ $tenant->company_address }}, {{ $tenant->company_city }}, {{ $tenant->company_county }}, {{ $tenant->company_zip }}</p></div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- BOTS --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100"><h2 class="text-sm font-semibold text-slate-900">Boți</h2></div>
        <table class="w-full text-sm">
            <thead><tr class="border-b border-slate-100">
                <th class="text-left px-5 py-2.5 text-[10px] text-slate-400 font-semibold uppercase">Bot</th>
                <th class="text-center px-3 py-2.5 text-[10px] text-slate-400 font-semibold uppercase">Status</th>
                <th class="text-center px-3 py-2.5 text-[10px] text-slate-400 font-semibold uppercase">Apeluri</th>
                <th class="text-center px-3 py-2.5 text-[10px] text-slate-400 font-semibold uppercase">Conv.</th>
                <th class="text-right px-5 py-2.5 text-[10px] text-slate-400 font-semibold uppercase">Cost</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($bots as $bot)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3"><a href="{{ route('admin.bots.show', $bot) }}" class="font-medium text-blue-600 hover:text-blue-800">{{ $bot->name }}</a></td>
                    <td class="px-3 py-3 text-center"><span class="px-2 py-0.5 rounded-full text-[10px] font-medium {{ $bot->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $bot->is_active ? 'Activ' : 'Inactiv' }}</span></td>
                    <td class="px-3 py-3 text-center text-slate-600">{{ $bot->calls_count }}</td>
                    <td class="px-3 py-3 text-center text-slate-600">{{ $bot->conversations_count }}</td>
                    <td class="px-5 py-3 text-right font-mono text-xs text-slate-600">{{ number_format(($bot->calls_sum_cost_cents ?? 0) / 100, 4) }}€</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Users --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <h2 class="text-sm font-semibold text-slate-900 mb-3">Utilizatori</h2>
        @foreach($users as $user)
        <div class="flex items-center justify-between py-2 {{ !$loop->last ? 'border-b border-slate-50' : '' }}">
            <div><p class="text-sm font-medium text-slate-900">{{ $user->name }}</p><p class="text-xs text-slate-400">{{ $user->email }}</p></div>
            <div class="flex items-center gap-2">
                <span class="text-[10px] text-slate-400">{{ $user->getRoleNames()->implode(', ') ?: '—' }}</span>
                <span class="text-xs text-slate-400">{{ $user->last_login_at?->diffForHumans() ?? 'Niciodată' }}</span>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Recent Conversations --}}
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100"><h2 class="text-sm font-semibold text-slate-900">Conversații recente</h2></div>
        @if($conversations->count())
        <table class="w-full text-sm">
            <thead><tr class="border-b border-slate-100">
                <th class="text-left px-5 py-2 text-[10px] text-slate-400 uppercase">ID</th>
                <th class="text-left px-3 py-2 text-[10px] text-slate-400 uppercase">Bot</th>
                <th class="text-center px-3 py-2 text-[10px] text-slate-400 uppercase">Mesaje</th>
                <th class="text-right px-3 py-2 text-[10px] text-slate-400 uppercase">Cost AI</th>
                <th class="text-center px-3 py-2 text-[10px] text-slate-400 uppercase">Status</th>
                <th class="text-right px-5 py-2 text-[10px] text-slate-400 uppercase">Data</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($conversations as $conv)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-2.5"><a href="{{ route('admin.conversations.show', $conv) }}" class="text-blue-600 hover:text-blue-800">#{{ $conv->id }}</a></td>
                    <td class="px-3 py-2.5 text-slate-600">{{ $conv->bot?->name ?? '—' }}</td>
                    <td class="px-3 py-2.5 text-center text-slate-600">{{ $conv->messages_count }}</td>
                    <td class="px-3 py-2.5 text-right font-mono text-xs text-slate-600">{{ ($conv->real_cost_cents ?? 0) > 0 ? number_format($conv->real_cost_cents / 100, 4) . '€' : '—' }}</td>
                    <td class="px-3 py-2.5 text-center"><span class="px-2 py-0.5 rounded-full text-[10px] {{ $conv->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $conv->status === 'active' ? 'Activă' : 'Încheiată' }}</span></td>
                    <td class="px-5 py-2.5 text-right text-xs text-slate-400">{{ $conv->created_at->format('d.m.Y H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="px-5 py-8 text-center text-sm text-slate-400">Nicio conversație.</div>
        @endif
    </div>

</div>
@endsection
