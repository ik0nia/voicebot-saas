{{-- Bot page header — clean, simple, no health score (that's in Overview tab) --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div class="flex items-center gap-4">
        <h1 id="bot-name-display" class="text-2xl font-bold text-slate-900">{{ $bot->name }}</h1>
        <form method="POST" action="{{ route('dashboard.bots.toggle', $bot) }}" class="inline">
            @csrf
            @method('PATCH')
            <button type="submit"
                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition-colors cursor-pointer
                {{ $bot->is_active
                    ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100'
                    : 'bg-slate-100 text-slate-500 border border-slate-200 hover:bg-slate-200' }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $bot->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                {{ $bot->is_active ? 'Activ' : 'Inactiv' }}
            </button>
        </form>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('dashboard.bots.testVocal', $bot) }}"
           class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-white transition-colors shadow-sm"
           style="background: linear-gradient(135deg, #991b1b, #dc2626); box-shadow: 0 2px 8px rgba(220,38,38,.3);">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m-4-4h8m-4-14a3 3 0 013 3v4a3 3 0 01-6 0V7a3 3 0 013-3z" /></svg>
            Test Vocal
        </a>
        <a href="{{ route('public.demo', $bot->slug) }}" target="_blank"
           class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
            Demo Link
        </a>
    </div>
</div>
