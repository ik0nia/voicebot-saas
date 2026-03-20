@extends('layouts.dashboard')

@section('title', 'Numere Telefon')

@section('breadcrumb')
    <span class="text-slate-400">/</span>
    <span class="font-medium text-slate-700">Numere Telefon</span>
@endsection

@section('content')
    {{-- Flash message --}}
    @if(session('success'))
        <div class="mb-6 flex items-center gap-3 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Numere de telefon</h1>
            <p class="mt-1 text-sm text-slate-500">Gestionează numerele de telefon asociate boților tăi.</p>
        </div>
        <button onclick="document.getElementById('add-number-modal').classList.remove('hidden')"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-900 transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Adaugă număr
        </button>
    </div>

    {{-- Summary cards --}}
    @php
        $totalNumbers = $numbers->total();
        $activeNumbers = App\Models\PhoneNumber::where('is_active', true)->count();
        $totalCostCents = App\Models\PhoneNumber::sum('monthly_cost_cents');
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <x-dashboard.stat-card
            title="Total numere"
            :value="$totalNumbers"
            color="blue"
            :icon="'<svg class=\'w-6 h-6\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'currentColor\' stroke-width=\'1.75\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' d=\'M7 20l4-16m2 16l4-16M6 9h14M4 15h14\' /></svg>'"
        />
        <x-dashboard.stat-card
            title="Numere active"
            :value="$activeNumbers"
            color="emerald"
            :icon="'<svg class=\'w-6 h-6\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'currentColor\' stroke-width=\'1.75\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' d=\'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z\' /></svg>'"
        />
        <x-dashboard.stat-card
            title="Cost lunar total"
            :value="number_format($totalCostCents / 100, 2, ',', '.') . ' EUR'"
            color="amber"
            :icon="'<svg class=\'w-6 h-6\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'currentColor\' stroke-width=\'1.75\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' d=\'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z\' /></svg>'"
        />
    </div>

    {{-- Add Number Modal --}}
    <div id="add-number-modal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 py-6">
            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-slate-900/50 transition-opacity" onclick="document.getElementById('add-number-modal').classList.add('hidden')"></div>

            {{-- Modal content --}}
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-lg p-6 z-10">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-bold text-slate-900">Adaugă număr de telefon</h2>
                    <button onclick="document.getElementById('add-number-modal').classList.add('hidden')"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('dashboard.numbers.store') }}" class="space-y-4">
                    @csrf

                    {{-- Număr de telefon --}}
                    <div>
                        <label for="number" class="block text-sm font-medium text-slate-700 mb-1">Număr de telefon</label>
                        <input type="text" name="number" id="number" required placeholder="+40721234567"
                               value="{{ old('number') }}"
                               class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 placeholder-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition" />
                        @error('number')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Nume prietenos --}}
                    <div>
                        <label for="friendly_name" class="block text-sm font-medium text-slate-700 mb-1">Nume prietenos <span class="text-slate-400">(opțional)</span></label>
                        <input type="text" name="friendly_name" id="friendly_name" placeholder="Linia principală"
                               value="{{ old('friendly_name') }}"
                               class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 placeholder-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition" />
                    </div>

                    {{-- Provider --}}
                    <div>
                        <label for="provider" class="block text-sm font-medium text-slate-700 mb-1">Provider</label>
                        <select name="provider" id="provider"
                                class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition">
                            <option value="twilio" {{ old('provider') === 'twilio' ? 'selected' : '' }}>Twilio</option>
                            <option value="manual" {{ old('provider') === 'manual' ? 'selected' : '' }}>Manual</option>
                        </select>
                    </div>

                    {{-- Asociază cu bot --}}
                    <div>
                        <label for="bot_id" class="block text-sm font-medium text-slate-700 mb-1">Asociază cu bot <span class="text-slate-400">(opțional)</span></label>
                        <select name="bot_id" id="bot_id"
                                class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition">
                            <option value="">— Fără bot asociat —</option>
                            @foreach($bots as $bot)
                                <option value="{{ $bot->id }}" {{ old('bot_id') == $bot->id ? 'selected' : '' }}>{{ $bot->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Cost lunar --}}
                    <div>
                        <label for="monthly_cost_cents" class="block text-sm font-medium text-slate-700 mb-1">Cost lunar <span class="text-slate-400">(cenți EUR)</span></label>
                        <input type="number" name="monthly_cost_cents" id="monthly_cost_cents" value="{{ old('monthly_cost_cents', 100) }}" min="0"
                               class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 placeholder-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition" />
                        <p class="mt-1 text-xs text-slate-400">100 cenți = 1,00 EUR</p>
                    </div>

                    {{-- Buttons --}}
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" onclick="document.getElementById('add-number-modal').classList.add('hidden')"
                                class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                            Anulează
                        </button>
                        <button type="submit"
                                class="rounded-lg bg-red-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-900 transition-colors">
                            Adaugă
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Numbers Table --}}
    @if($numbers->count() > 0)
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50">
                            <th class="px-4 py-3 font-semibold text-slate-600">Număr</th>
                            <th class="px-4 py-3 font-semibold text-slate-600">Nume</th>
                            <th class="px-4 py-3 font-semibold text-slate-600 hidden md:table-cell">Bot asociat</th>
                            <th class="px-4 py-3 font-semibold text-slate-600 hidden lg:table-cell">Provider</th>
                            <th class="px-4 py-3 font-semibold text-slate-600 hidden lg:table-cell">Cost lunar</th>
                            <th class="px-4 py-3 font-semibold text-slate-600">Status</th>
                            <th class="px-4 py-3 font-semibold text-slate-600 text-right">Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($numbers as $phoneNumber)
                            <tr class="hover:bg-slate-50 transition-colors" id="row-{{ $phoneNumber->id }}">
                                {{-- Număr --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        @if(str_starts_with($phoneNumber->number, '+40'))
                                            <span class="text-base leading-none" title="România">&#x1F1F7;&#x1F1F4;</span>
                                        @endif
                                        <span class="font-medium text-slate-900">{{ $phoneNumber->number }}</span>
                                    </div>
                                </td>

                                {{-- Nume --}}
                                <td class="px-4 py-3 text-slate-600">
                                    {{ $phoneNumber->friendly_name ?? '—' }}
                                </td>

                                {{-- Bot asociat --}}
                                <td class="px-4 py-3 hidden md:table-cell">
                                    {{-- Inline edit form (hidden by default) --}}
                                    <div id="edit-bot-{{ $phoneNumber->id }}" class="hidden">
                                        <form method="POST" action="{{ route('dashboard.numbers.update', $phoneNumber) }}" class="flex items-center gap-2">
                                            @csrf
                                            @method('PUT')
                                            <select name="bot_id"
                                                    class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition">
                                                <option value="">— Fără bot —</option>
                                                @foreach($bots as $bot)
                                                    <option value="{{ $bot->id }}" {{ $phoneNumber->bot_id == $bot->id ? 'selected' : '' }}>{{ $bot->name }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="p-1.5 rounded-lg text-green-600 hover:bg-green-50 transition-colors" title="Salvează">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>
                                            <button type="button" onclick="toggleEditBot({{ $phoneNumber->id }})" class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 transition-colors" title="Anulează">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                    {{-- Display (shown by default) --}}
                                    <div id="display-bot-{{ $phoneNumber->id }}">
                                        @if($phoneNumber->bot)
                                            <a href="{{ route('dashboard.bots.show', $phoneNumber->bot) }}" class="text-red-800 hover:text-red-900 font-medium transition-colors">
                                                {{ $phoneNumber->bot->name }}
                                            </a>
                                        @else
                                            <span class="text-slate-400">Neasociat</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Provider --}}
                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $phoneNumber->provider === 'twilio' ? 'bg-red-50 text-red-800' : 'bg-slate-100 text-slate-600' }}">
                                        {{ ucfirst($phoneNumber->provider ?? 'manual') }}
                                    </span>
                                </td>

                                {{-- Cost lunar --}}
                                <td class="px-4 py-3 hidden lg:table-cell text-slate-600">
                                    {{ number_format(($phoneNumber->monthly_cost_cents ?? 0) / 100, 2, ',', '.') }} EUR
                                </td>

                                {{-- Status --}}
                                <td class="px-4 py-3">
                                    @if($phoneNumber->is_active)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                            Activ
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-500">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                            Inactiv
                                        </span>
                                    @endif
                                </td>

                                {{-- Acțiuni --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1.5">
                                        {{-- Edit bot association --}}
                                        <button onclick="toggleEditBot({{ $phoneNumber->id }})" title="Editează asocierea"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 hover:text-slate-700 transition-colors">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>

                                        {{-- Toggle active --}}
                                        <form method="POST" action="{{ route('dashboard.numbers.toggle', $phoneNumber) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" title="{{ $phoneNumber->is_active ? 'Dezactivează' : 'Activează' }}"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg border {{ $phoneNumber->is_active ? 'border-green-200 bg-green-50 text-green-600 hover:bg-green-100' : 'border-slate-200 bg-slate-50 text-slate-400 hover:bg-slate-100' }} transition-colors">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    @if($phoneNumber->is_active)
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    @else
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    @endif
                                                </svg>
                                            </button>
                                        </form>

                                        {{-- Delete --}}
                                        <form method="POST" action="{{ route('dashboard.numbers.destroy', $phoneNumber) }}"
                                              onsubmit="return confirm('Ești sigur că vrei să eliberezi numărul {{ $phoneNumber->number }}? Această acțiune este ireversibilă.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Șterge"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-red-200 bg-white text-red-400 hover:bg-red-50 hover:text-red-600 transition-colors">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($numbers->hasPages())
                <div class="px-4 py-3 border-t border-slate-200">
                    {{ $numbers->links() }}
                </div>
            @endif
        </div>
    @else
        {{-- Empty state --}}
        <div class="flex flex-col items-center justify-center py-16 px-4 bg-white rounded-xl border border-slate-200">
            <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 mb-1">Nu ai numere de telefon încă</h3>
            <p class="text-sm text-slate-500 mb-6 text-center max-w-sm">Adaugă primul număr pentru a conecta un bot.</p>
            <button onclick="document.getElementById('add-number-modal').classList.remove('hidden')"
                    class="inline-flex items-center gap-2 rounded-lg bg-red-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-900 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Adaugă primul număr
            </button>
        </div>
    @endif

    {{-- Info box --}}
    <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 flex items-start gap-3">
        <svg class="w-5 h-5 text-red-700 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="text-sm text-red-800">
            Pentru a cumpăra numere automat prin Twilio, configurează credențialele în
            <a href="/dashboard/setari" class="font-semibold underline hover:text-red-900 transition-colors">Setări &rarr; Integrări</a>.
        </p>
    </div>

    {{-- Show modal on validation errors --}}
    @if($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('add-number-modal').classList.remove('hidden');
            });
        </script>
    @endif
@endsection

@push('scripts')
<script>
    function toggleEditBot(id) {
        var editEl = document.getElementById('edit-bot-' + id);
        var displayEl = document.getElementById('display-bot-' + id);
        editEl.classList.toggle('hidden');
        displayEl.classList.toggle('hidden');
    }
</script>
@endpush
