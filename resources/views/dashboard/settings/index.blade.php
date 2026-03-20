@extends('layouts.dashboard')

@section('title', 'Setări')
@section('breadcrumb')
<span class="text-slate-900 font-medium">Setări</span>
@endsection

@section('content')
<div class="space-y-6">

    {{-- Page Header --}}
    <x-dashboard.page-header title="Setări" description="Gestionează profilul, compania și preferințele contului tău." />

    {{-- Success Message --}}
    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
            <div class="flex items-center gap-3">
                <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-sm font-medium text-emerald-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    {{-- Error Messages --}}
    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-4">
            <div class="flex items-start gap-3">
                <svg class="h-5 w-5 text-red-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.072 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                <ul class="text-sm text-red-700 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Tab Navigation --}}
    <div class="border-b border-slate-200">
        <nav class="-mb-px flex gap-x-1 overflow-x-auto" aria-label="Tabs">
            @php
                $tabs = [
                    'profile' => 'Profil',
                    'company' => 'Companie',
                    'notifications' => 'Notificări',
                    'api' => 'Chei API',
                    'webhooks' => 'Webhooks',
                    'danger' => 'Pericol',
                ];
            @endphp
            @foreach($tabs as $key => $label)
                <a href="{{ url('/dashboard/setari?tab=' . $key) }}"
                   class="whitespace-nowrap border-b-2 px-4 py-3 text-sm font-medium transition-colors
                          {{ $tab === $key
                              ? 'border-red-800 text-red-800'
                              : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>
    </div>

    {{-- ============================================================ --}}
    {{-- TAB: Profil --}}
    {{-- ============================================================ --}}
    @if($tab === 'profile')

        {{-- Profile Form --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-900">Informații profil</h2>
            <p class="mt-1 text-sm text-slate-500">Actualizează informațiile tale personale.</p>

            <form method="POST" action="{{ url('/dashboard/setari/profile') }}" class="mt-6 space-y-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    {{-- Nume --}}
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700">Nume</label>
                        <input type="text" name="name" id="name"
                               value="{{ old('name', auth()->user()->name) }}"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 focus:outline-none transition-colors"
                               required>
                        @error('name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                        <input type="email" name="email" id="email"
                               value="{{ old('email', auth()->user()->email) }}"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 focus:outline-none transition-colors"
                               required>
                        @error('email')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Telefon --}}
                    <div>
                        <label for="phone" class="block text-sm font-medium text-slate-700">Telefon</label>
                        <input type="text" name="phone" id="phone"
                               value="{{ old('phone', auth()->user()->phone) }}"
                               placeholder="+40 7XX XXX XXX"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 focus:outline-none transition-colors">
                        @error('phone')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Fus orar --}}
                    <div>
                        <label for="timezone" class="block text-sm font-medium text-slate-700">Fus orar</label>
                        <select name="timezone" id="timezone"
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm focus:border-red-700 focus:ring-2 focus:ring-red-700/20 focus:outline-none transition-colors">
                            @php
                                $timezones = [
                                    'Europe/Bucharest' => 'Europe/Bucharest (EET/EEST)',
                                    'Europe/London' => 'Europe/London (GMT/BST)',
                                    'Europe/Berlin' => 'Europe/Berlin (CET/CEST)',
                                    'Europe/Paris' => 'Europe/Paris (CET/CEST)',
                                    'Europe/Madrid' => 'Europe/Madrid (CET/CEST)',
                                    'Europe/Rome' => 'Europe/Rome (CET/CEST)',
                                    'Europe/Amsterdam' => 'Europe/Amsterdam (CET/CEST)',
                                    'Europe/Brussels' => 'Europe/Brussels (CET/CEST)',
                                    'Europe/Vienna' => 'Europe/Vienna (CET/CEST)',
                                    'Europe/Zurich' => 'Europe/Zurich (CET/CEST)',
                                    'Europe/Stockholm' => 'Europe/Stockholm (CET/CEST)',
                                    'Europe/Warsaw' => 'Europe/Warsaw (CET/CEST)',
                                    'Europe/Athens' => 'Europe/Athens (EET/EEST)',
                                    'Europe/Helsinki' => 'Europe/Helsinki (EET/EEST)',
                                    'Europe/Istanbul' => 'Europe/Istanbul (TRT)',
                                    'Europe/Moscow' => 'Europe/Moscow (MSK)',
                                    'America/New_York' => 'America/New_York (EST/EDT)',
                                    'America/Chicago' => 'America/Chicago (CST/CDT)',
                                    'America/Denver' => 'America/Denver (MST/MDT)',
                                    'America/Los_Angeles' => 'America/Los_Angeles (PST/PDT)',
                                    'Asia/Tokyo' => 'Asia/Tokyo (JST)',
                                    'Asia/Shanghai' => 'Asia/Shanghai (CST)',
                                    'Asia/Dubai' => 'Asia/Dubai (GST)',
                                    'Australia/Sydney' => 'Australia/Sydney (AEST/AEDT)',
                                    'UTC' => 'UTC',
                                ];
                                $currentTz = old('timezone', auth()->user()->timezone ?? 'Europe/Bucharest');
                            @endphp
                            @foreach($timezones as $value => $label)
                                <option value="{{ $value }}" {{ $currentTz === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('timezone')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-red-800 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-700/20 transition-colors">
                        Salvează profilul
                    </button>
                </div>
            </form>
        </div>

        {{-- Change Password --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-900">Schimbă parola</h2>
            <p class="mt-1 text-sm text-slate-500">Asigură-te că folosești o parolă puternică de cel puțin 8 caractere.</p>

            <form method="POST" action="{{ url('/dashboard/setari/password') }}" class="mt-6 space-y-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                    {{-- Parola curentă --}}
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-slate-700">Parola curentă</label>
                        <input type="password" name="current_password" id="current_password"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 focus:outline-none transition-colors"
                               required>
                        @error('current_password')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Parolă nouă --}}
                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700">Parolă nouă</label>
                        <input type="password" name="password" id="password"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 focus:outline-none transition-colors"
                               required>
                        @error('password')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Confirmă parola --}}
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirmă parola</label>
                        <input type="password" name="password_confirmation" id="password_confirmation"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 focus:outline-none transition-colors"
                               required>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-red-800 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-700/20 transition-colors">
                        Schimbă parola
                    </button>
                </div>
            </form>
        </div>

    @endif

    {{-- ============================================================ --}}
    {{-- TAB: Companie --}}
    {{-- ============================================================ --}}
    @if($tab === 'company')

        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-900">Datele companiei</h2>
            <p class="mt-1 text-sm text-slate-500">Informații despre organizația ta.</p>

            <form method="POST" action="{{ url('/dashboard/setari/company') }}" class="mt-6 space-y-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    {{-- Nume companie --}}
                    <div>
                        <label for="company_name" class="block text-sm font-medium text-slate-700">Nume companie</label>
                        <input type="text" name="name" id="company_name"
                               value="{{ old('name', $tenant->name ?? '') }}"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 focus:outline-none transition-colors"
                               required>
                        @error('name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Website --}}
                    <div>
                        <label for="website" class="block text-sm font-medium text-slate-700">Website</label>
                        <input type="url" name="settings[website]" id="website"
                               value="{{ old('settings.website', $tenant->settings['website'] ?? '') }}"
                               placeholder="https://exemplu.ro"
                               class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 focus:outline-none transition-colors">
                        @error('settings.website')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Industrie --}}
                    <div>
                        <label for="industry" class="block text-sm font-medium text-slate-700">Industrie</label>
                        <select name="settings[industry]" id="industry"
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm focus:border-red-700 focus:ring-2 focus:ring-red-700/20 focus:outline-none transition-colors">
                            @php
                                $industries = [
                                    '' => 'Selectează industria',
                                    'tehnologie' => 'Tehnologie',
                                    'sanatate' => 'Sănătate',
                                    'finante' => 'Finanțe',
                                    'educatie' => 'Educație',
                                    'retail' => 'Retail',
                                    'altele' => 'Altele',
                                ];
                                $currentIndustry = old('settings.industry', $tenant->settings['industry'] ?? '');
                            @endphp
                            @foreach($industries as $value => $label)
                                <option value="{{ $value }}" {{ $currentIndustry === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('settings.industry')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Plan curent --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Plan curent</label>
                        <div class="mt-1.5 flex items-center gap-3">
                            @php
                                $plan = $tenant->plan ?? 'starter';
                                $planLabels = ['starter' => 'Starter', 'pro' => 'Pro', 'enterprise' => 'Enterprise'];
                                $planColors = [
                                    'starter' => 'bg-slate-100 text-slate-700',
                                    'pro' => 'bg-red-100 text-red-800',
                                    'enterprise' => 'bg-red-100 text-red-800',
                                ];
                            @endphp
                            <span class="inline-flex items-center rounded-full px-3 py-1.5 text-sm font-semibold {{ $planColors[$plan] ?? $planColors['starter'] }}">
                                {{ $planLabels[$plan] ?? 'Starter' }}
                            </span>
                            @if($plan !== 'enterprise')
                                <a href="/dashboard/facturare" class="text-sm font-medium text-red-800 hover:text-red-900 transition-colors">
                                    Upgrade plan
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-red-800 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-700/20 transition-colors">
                        Salvează compania
                    </button>
                </div>
            </form>
        </div>

    @endif

    {{-- ============================================================ --}}
    {{-- TAB: Notificări --}}
    {{-- ============================================================ --}}
    @if($tab === 'notifications')

        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-900">Preferințe notificări</h2>
            <p class="mt-1 text-sm text-slate-500">Alege ce notificări dorești să primești.</p>

            <form method="POST" action="{{ url('/dashboard/setari/notifications') }}" class="mt-6 space-y-5">
                @csrf
                @method('PUT')

                @php
                    $prefs = auth()->user()->notification_preferences ?? [];
                    $notifications = [
                        'call_failed' => ['label' => 'Apel eșuat', 'desc' => 'Primește o notificare când un apel nu poate fi finalizat.', 'default' => true],
                        'usage_80' => ['label' => 'Utilizare 80% plan', 'desc' => 'Alertă când ai consumat 80% din resursele planului.', 'default' => true],
                        'usage_100' => ['label' => 'Utilizare 100% plan', 'desc' => 'Alertă când ai atins limita planului.', 'default' => true],
                        'invoice_issued' => ['label' => 'Factură emisă', 'desc' => 'Notificare la emiterea unei facturi noi.', 'default' => true],
                        'weekly_report' => ['label' => 'Raport săptămânal', 'desc' => 'Sumar săptămânal cu activitatea boților tăi.', 'default' => true],
                        'each_call_completed' => ['label' => 'Fiecare apel completat', 'desc' => 'Notificare individuală la finalizarea fiecărui apel.', 'default' => false],
                    ];
                @endphp

                <div class="space-y-4">
                    @foreach($notifications as $key => $notif)
                        <label class="flex items-start gap-4 p-4 rounded-lg border border-slate-200 hover:bg-slate-50 transition-colors cursor-pointer">
                            <input type="checkbox" name="{{ $key }}" value="1"
                                   {{ (isset($prefs[$key]) ? $prefs[$key] : $notif['default']) ? 'checked' : '' }}
                                   class="mt-0.5 h-4 w-4 rounded border-slate-300 text-red-800 focus:ring-red-700/20">
                            <div>
                                <span class="text-sm font-medium text-slate-900">{{ $notif['label'] }}</span>
                                <p class="text-sm text-slate-500 mt-0.5">{{ $notif['desc'] }}</p>
                            </div>
                        </label>
                    @endforeach
                </div>

                <div class="flex items-center justify-between pt-2">
                    <p class="text-sm text-slate-500">
                        Notificările sunt trimise pe email la <span class="font-medium text-slate-700">{{ auth()->user()->email }}</span>
                    </p>
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-red-800 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-700/20 transition-colors">
                        Salvează notificările
                    </button>
                </div>
            </form>
        </div>

    @endif

    {{-- ============================================================ --}}
    {{-- TAB: Chei API --}}
    {{-- ============================================================ --}}
    @if($tab === 'api')

        {{-- Info --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-900">Chei API</h2>
            <p class="mt-1 text-sm text-slate-500">Folosește cheile API pentru a accesa Sambla API programatic.</p>

            {{-- Generate New Key --}}
            <form method="POST" action="{{ url('/dashboard/setari/api-keys') }}" class="mt-6">
                @csrf
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1">
                        <label for="key_name" class="sr-only">Nume cheie</label>
                        <input type="text" name="name" id="key_name"
                               placeholder="Nume cheie (ex: Integrare CRM)"
                               class="block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 focus:outline-none transition-colors"
                               required>
                    </div>
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-800 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-700/20 transition-colors whitespace-nowrap">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Generează cheie
                    </button>
                </div>
            </form>
        </div>

        {{-- Existing Tokens --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200">
                <h3 class="text-sm font-semibold text-slate-900">Chei existente</h3>
            </div>

            @php
                $tokens = auth()->user()->tokens ?? collect();
            @endphp

            @if($tokens->count() > 0)
                <div class="divide-y divide-slate-200">
                    @foreach($tokens as $token)
                        <div class="flex items-center justify-between px-6 py-4">
                            <div>
                                <p class="text-sm font-medium text-slate-900">{{ $token->name }}</p>
                                <div class="flex items-center gap-4 mt-1">
                                    <span class="text-xs text-slate-500">Creată: {{ $token->created_at->format('d.m.Y H:i') }}</span>
                                    @if($token->last_used_at)
                                        <span class="text-xs text-slate-500">Ultima utilizare: {{ $token->last_used_at->format('d.m.Y H:i') }}</span>
                                    @else
                                        <span class="text-xs text-slate-400">Neutilizată</span>
                                    @endif
                                </div>
                            </div>
                            <form method="POST" action="{{ url('/dashboard/setari/api-keys/' . $token->id) }}"
                                  onsubmit="return confirm('Sigur vrei să revoci această cheie API? Acțiunea este ireversibilă.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Revocă
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="px-6 py-10 text-center">
                    <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                    </svg>
                    <p class="mt-3 text-sm text-slate-500">Nu ai nicio cheie API. Generează una pentru a începe.</p>
                </div>
            @endif
        </div>

        {{-- Code Example --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="text-sm font-semibold text-slate-900">Exemplu de utilizare</h3>
            <p class="mt-1 text-sm text-slate-500">Folosește cheia API în header-ul Authorization:</p>

            <div class="mt-4 rounded-lg bg-slate-900 p-4 overflow-x-auto">
                <pre class="text-sm text-slate-100 font-mono"><code>curl -H "Authorization: Bearer YOUR_API_KEY" \
  https://sambla.ro/api/v1/bots</code></pre>
            </div>
        </div>

    @endif

    {{-- ============================================================ --}}
    {{-- TAB: Webhooks --}}
    {{-- ============================================================ --}}
    @if($tab === 'webhooks')

        <div class="bg-white rounded-xl border border-slate-200 p-6 relative">
            {{-- Coming Soon Badge --}}
            <div class="absolute top-4 right-4">
                <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                    În curând
                </span>
            </div>

            <h2 class="text-lg font-semibold text-slate-900">Webhooks</h2>
            <p class="mt-1 text-sm text-slate-500">Configurează URL-uri pentru a primi notificări despre evenimente.</p>

            <div class="mt-6 space-y-5 opacity-50 pointer-events-none" aria-disabled="true">
                {{-- Webhook URL --}}
                <div>
                    <label for="webhook_url" class="block text-sm font-medium text-slate-700">URL Webhook</label>
                    <input type="url" name="webhook_url" id="webhook_url"
                           placeholder="https://exemplu.ro/api/webhook"
                           disabled
                           class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 cursor-not-allowed">
                </div>

                {{-- Event Types --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-3">Tipuri de evenimente</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @php
                            $webhookEvents = [
                                'call.started' => 'call.started — Apel pornit',
                                'call.ended' => 'call.ended — Apel terminat',
                                'call.failed' => 'call.failed — Apel eșuat',
                                'transcript.ready' => 'transcript.ready — Transcriere pregătită',
                            ];
                        @endphp
                        @foreach($webhookEvents as $value => $label)
                            <label class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 cursor-not-allowed">
                                <input type="checkbox" name="events[]" value="{{ $value }}" disabled
                                       class="h-4 w-4 rounded border-slate-300 text-red-800">
                                <span class="text-sm text-slate-700">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Save Button --}}
                <div class="flex justify-end pt-2">
                    <button type="button" disabled
                            class="inline-flex items-center gap-2 rounded-lg bg-red-800 px-5 py-2.5 text-sm font-semibold text-white shadow-sm cursor-not-allowed">
                        Salvează webhook
                    </button>
                </div>
            </div>

            <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4">
                <div class="flex items-start gap-3">
                    <svg class="h-5 w-5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm text-amber-800">
                        Coming soon — funcționalitatea webhook-urilor este în dezvoltare. Vei fi notificat când va fi disponibilă.
                    </p>
                </div>
            </div>
        </div>

    @endif

    {{-- ============================================================ --}}
    {{-- TAB: Pericol --}}
    {{-- ============================================================ --}}
    @if($tab === 'danger')

        <div class="bg-white rounded-xl border-2 border-red-200 p-6">
            <h2 class="text-lg font-semibold text-red-700">Zonă periculoasă</h2>
            <p class="mt-2 text-sm text-slate-600">
                Ștergerea contului este permanentă. Toate datele, boții, apelurile și setările vor fi șterse irecuperabil.
            </p>

            <div class="mt-6">
                <button type="button" id="delete-account-btn" onclick="document.getElementById('delete-confirmation').classList.remove('hidden'); this.classList.add('hidden');"
                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500/20 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Șterge contul
                </button>

                {{-- Confirmation Box --}}
                <div id="delete-confirmation" class="hidden mt-6 rounded-lg border border-red-200 bg-red-50 p-5">
                    <form method="POST" action="{{ url('/dashboard/setari/account') }}">
                        @csrf
                        @method('DELETE')

                        <p class="text-sm font-medium text-red-800">
                            Pentru a confirma ștergerea, scrie <span class="font-bold">STERGE</span> în câmpul de mai jos:
                        </p>

                        <input type="text" name="confirmation" id="delete-confirmation-input"
                               placeholder="Scrie STERGE pentru a confirma"
                               autocomplete="off"
                               class="mt-3 block w-full sm:w-80 rounded-lg border border-red-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 focus:outline-none transition-colors"
                               required>
                        @error('confirmation')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror

                        <div class="mt-4 flex items-center gap-3">
                            <button type="submit"
                                    class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500/20 transition-colors">
                                Confirm ștergerea contului
                            </button>
                            <button type="button"
                                    onclick="document.getElementById('delete-confirmation').classList.add('hidden'); document.getElementById('delete-account-btn').classList.remove('hidden');"
                                    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 transition-colors">
                                Anulează
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    @endif

</div>
@endsection
