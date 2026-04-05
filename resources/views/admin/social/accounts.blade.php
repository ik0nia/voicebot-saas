@extends('layouts.admin')

@section('title', 'Conturi Social Media')
@section('breadcrumb')
    <a href="{{ route('admin.social.index') }}" class="text-slate-400 hover:text-slate-600">Social Media</a>
    <span class="mx-1.5 text-slate-300">/</span>
    Conturi
@endsection

@section('content')
<div class="space-y-6">

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.social.index') }}" class="p-2 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Conturi Social Media</h1>
            <p class="text-sm text-slate-500 mt-1">Configureaza conturile pentru fiecare platforma</p>
        </div>
    </div>

    {{-- API Keys Settings --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <div class="flex items-center gap-2 mb-4">
            <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
            <h2 class="text-lg font-semibold text-slate-900">API Keys</h2>
        </div>
        <form method="POST" action="{{ route('admin.social.apikeys.save') }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Gemini API Key</label>
                    @php
                        $hasGeminiKey = DB::table('settings')->where('key', 'gemini_api_key')->exists();
                    @endphp
                    <input type="password" name="gemini_api_key" value="{{ $hasGeminiKey ? '••••••••••' : '' }}" placeholder="AIza..."
                           class="w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500"
                           onfocus="if(this.value==='••••••••••')this.value=''">
                    <p class="text-xs text-slate-400 mt-1">Pentru generarea de imagini. <a href="https://aistudio.google.com/apikey" target="_blank" class="text-red-600 hover:underline">Obtine cheie</a> @if($hasGeminiKey)<span class="text-green-600">&#10003; Configurata</span>@endif</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Gemini Image Model</label>
                    <input type="text" name="gemini_image_model" value="{{ DB::table('settings')->where('key', 'gemini_image_model')->value('value') ?: 'gemini-3.1-flash' }}" placeholder="gemini-3.1-flash"
                           class="w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500">
                    <p class="text-xs text-slate-400 mt-1">Modelul Gemini pentru imagini</p>
                </div>
            </div>
            <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Salveaza API Keys
            </button>
        </form>
    </div>

    @php
        $platforms = [
            'facebook' => ['label' => 'Facebook', 'color' => 'blue', 'icon' => 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z'],
            'instagram' => ['label' => 'Instagram', 'color' => 'pink', 'icon' => 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z'],
            'blog' => ['label' => 'Blog', 'color' => 'slate', 'icon' => null],
        ];
    @endphp

    @foreach($platforms as $platformKey => $pConfig)
        @php
            $account = $accounts->firstWhere('platform', $platformKey);
        @endphp
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    @if($pConfig['icon'])
                        <svg class="w-5 h-5 text-{{ $pConfig['color'] }}-600" fill="currentColor" viewBox="0 0 24 24"><path d="{{ $pConfig['icon'] }}"/></svg>
                    @else
                        <svg class="w-5 h-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                    @endif
                    <h2 class="text-lg font-semibold text-slate-900">{{ $pConfig['label'] }}</h2>
                    @if($account)
                        <span class="w-2 h-2 rounded-full bg-green-500" title="Configurat"></span>
                    @else
                        <span class="w-2 h-2 rounded-full bg-slate-300" title="Neconfigurat"></span>
                    @endif
                </div>
                <button type="button" onclick="generateBio('{{ $platformKey }}')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Genereaza Bio
                </button>
            </div>

            <form method="POST" action="{{ route('admin.social.accounts.save') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="platform" value="{{ $platformKey }}">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nume cont</label>
                        <input type="text" name="name" value="{{ $account?->name }}" required placeholder="Sambla Romania"
                               class="w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Platform ID</label>
                        <input type="text" name="platform_id" value="{{ $account?->platform_id }}" placeholder="Page ID / Account ID"
                               class="w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Access Token</label>
                        <input type="password" name="access_token" value="{{ $account?->access_token }}" placeholder="Token..."
                               class="w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500">
                    </div>
                </div>
                <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Salveaza
                </button>
            </form>

            {{-- Bio display --}}
            <div id="bio-{{ $platformKey }}" class="mt-4 hidden">
                <h3 class="text-sm font-semibold text-slate-700 mb-1">Bio generat:</h3>
                <div id="bio-content-{{ $platformKey }}" class="text-sm text-slate-600 bg-slate-50 rounded-lg p-3"></div>
            </div>
        </div>
    @endforeach
</div>

@push('scripts')
<script>
    function generateBio(platform) {
        const bioDiv = document.getElementById('bio-' + platform);
        const bioContent = document.getElementById('bio-content-' + platform);
        bioContent.textContent = 'Se genereaza...';
        bioDiv.classList.remove('hidden');

        fetch('{{ route("admin.social.generateBio") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ platform: platform })
        })
        .then(r => r.json())
        .then(data => {
            bioContent.textContent = data.bio || data.content || JSON.stringify(data);
        })
        .catch(err => {
            bioContent.textContent = 'Eroare: ' + err.message;
        });
    }
</script>
@endpush
@endsection
