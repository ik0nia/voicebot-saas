@extends('layouts.dashboard')

@section('title', $bot->name)

@section('breadcrumb')
    <span class="text-slate-400">/</span>
    <a href="{{ route('dashboard.bots.index') }}" class="text-slate-500 hover:text-slate-700 transition-colors">Boti</a>
    <span class="text-slate-400">/</span>
    <span class="font-medium text-slate-700">{{ $bot->name }}</span>
@endsection

@section('content')
    {{-- Flash message --}}
    @if(session('success'))
        <div class="mb-4 flex items-center gap-3 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Save toast --}}
    <div id="save-toast" class="hidden fixed top-6 right-6 z-50 flex items-center gap-2 rounded-lg bg-green-600 text-white px-4 py-2.5 text-sm font-medium shadow-lg">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        Salvat!
    </div>

    {{-- Header --}}
    @include('dashboard.bots.partials.header')

    {{-- Tab System with Alpine.js --}}
    <div x-data="{ tab: '{{ request('tab', 'overview') }}' }" @set-tab.window="tab = $event.detail" class="mt-6">

        {{-- Tab Navigation --}}
        <div class="bg-slate-50/80 rounded-xl p-1.5 -mx-4 lg:-mx-6 mx-0 shadow-sm">
            <nav class="flex gap-1 overflow-x-auto scrollbar-hide">
                @php
                    $tabs = [
                        ['id' => 'overview', 'label' => 'Prezentare', 'dot' => '🏠'],
                        ['id' => 'instructions', 'label' => 'Instructiuni', 'dot' => '💬'],
                        ['id' => 'personality', 'label' => 'Personalitate', 'dot' => '😊'],
                        ['id' => 'knowledge', 'label' => 'Knowledge Base', 'dot' => '📚'],
                        ['id' => 'voice', 'label' => 'Voce', 'dot' => '🎙️'],
                        ['id' => 'channels', 'label' => 'Canale & Integrari', 'dot' => '🔗'],
                        ['id' => 'stats', 'label' => 'Statistici', 'dot' => '📊'],
                    ];
                @endphp
                @foreach($tabs as $t)
                <button @click="tab = '{{ $t['id'] }}'; history.replaceState(null, '', '?tab={{ $t['id'] }}')"
                        :class="tab === '{{ $t['id'] }}' ? 'bg-red-700 text-white shadow-md' : 'text-slate-600 hover:bg-red-50 hover:text-red-800'"
                        class="flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium whitespace-nowrap rounded-lg transition-all duration-200">
                    <span class="text-sm">{{ $t['dot'] }}</span>
                    {{ $t['label'] }}
                </button>
                @endforeach
            </nav>
        </div>

        {{-- Tab Panels --}}
        <div class="mt-6">
            {{-- Overview --}}
            <div x-show="tab === 'overview'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                @include('dashboard.bots.partials.setup-guide')
            </div>

            {{-- Instructions --}}
            <div x-show="tab === 'instructions'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                @include('dashboard.bots.partials.tab-instructions')
            </div>

            {{-- Voice --}}
            <div x-show="tab === 'voice'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                @include('dashboard.bots.partials.tab-voice')
            </div>

            {{-- Personality --}}
            <div x-show="tab === 'personality'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                @include('dashboard.bots.partials.personality-settings')
            </div>

            {{-- Knowledge Base --}}
            <div x-show="tab === 'knowledge'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                @include('dashboard.bots.partials.tab-knowledge')
            </div>

            {{-- Channels & Integrations --}}
            <div x-show="tab === 'channels'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                @include('dashboard.bots.partials.tab-wordpress')
                @include('dashboard.bots.partials.tab-channels')
            </div>

            {{-- Stats --}}
            <div x-show="tab === 'stats'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                @include('dashboard.bots.partials.tab-stats')
            </div>
        </div>
    </div>

    {{-- Prompt Modal --}}
    @include('dashboard.bots.partials.prompt-modal')

    {{-- Global JavaScript --}}
    <script>
        const UPDATE_URL = "{{ route('dashboard.bots.updateField', $bot) }}";
        const CSRF_TOKEN = "{{ csrf_token() }}";

        function showToast(message) {
            const toast = document.getElementById('save-toast');
            if (message) toast.textContent = message;
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 2000);
        }

        function vcAction(url, method) {
            fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Content-Type': 'application/x-www-form-urlencoded' },
                body: method === 'DELETE' ? '_method=DELETE' : '',
            }).then(() => window.location.reload());
        }

        function updateField(field, value) {
            fetch(UPDATE_URL, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                body: JSON.stringify({ field, value })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('Salvat!');
                    if (field === 'name') document.getElementById('bot-name-display').textContent = value;
                }
            })
            .catch(() => alert('Eroare la salvare. Incearca din nou.'));
        }

        function openPromptModal() {
            document.getElementById('prompt-modal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closePromptModal() {
            document.getElementById('prompt-modal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        function savePrompt() {
            const value = document.getElementById('prompt-textarea').value;
            fetch(UPDATE_URL, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                body: JSON.stringify({ field: 'system_prompt', value })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) { showToast('Prompt salvat!'); closePromptModal(); location.reload(); }
            })
            .catch(() => alert('Eroare la salvare.'));
        }

        document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closePromptModal(); });
        document.getElementById('prompt-modal')?.addEventListener('click', function(e) { if (e.target === this) closePromptModal(); });
    </script>
@endsection
