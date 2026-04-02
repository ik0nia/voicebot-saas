{{-- Action Items — things that need attention --}}
@if($actionItems->isNotEmpty())
<div class="rounded-xl border border-amber-200 bg-amber-50/50 shadow-sm overflow-hidden">
    <div class="px-5 py-3 border-b border-amber-200 flex items-center gap-2">
        <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
        <h3 class="text-sm font-semibold text-amber-900">Necesita atentie ({{ $actionItems->count() }})</h3>
    </div>
    <div class="divide-y divide-amber-100">
        @foreach($actionItems->take(5) as $item)
        <div class="px-5 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3 min-w-0">
                @if($item['severity'] === 'error')
                    <div class="w-2 h-2 rounded-full bg-red-500 shrink-0"></div>
                @elseif($item['severity'] === 'warning')
                    <div class="w-2 h-2 rounded-full bg-amber-500 shrink-0"></div>
                @else
                    <div class="w-2 h-2 rounded-full bg-blue-400 shrink-0"></div>
                @endif
                <div class="min-w-0">
                    <p class="text-sm text-slate-800">{{ $item['message'] }}</p>
                    @if($item['type'] === 'bot')
                        <p class="text-xs text-slate-500">Bot: {{ $item['bot'] }}</p>
                    @endif
                </div>
            </div>
            @if($item['type'] === 'bot' && isset($item['bot_id']))
                <a href="/dashboard/boti/{{ $item['bot_id'] }}/editare" class="shrink-0 text-xs font-medium text-amber-700 hover:text-amber-900 hover:underline">Rezolva &rarr;</a>
            @elseif($item['type'] === 'leads')
                <a href="/dashboard/leads" class="shrink-0 text-xs font-medium text-amber-700 hover:text-amber-900 hover:underline">Vezi leads &rarr;</a>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endif
