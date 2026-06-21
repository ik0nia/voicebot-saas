@php
    /** @var string $step */
    $steps = [
        'niche'   => ['n' => 1, 'label' => 'Nișă',  'icon' => '🎯'],
        'website' => ['n' => 2, 'label' => 'Site',  'icon' => '🌐'],
        'agent'   => ['n' => 3, 'label' => 'Agent', 'icon' => '🤖'],
        'test'    => ['n' => 4, 'label' => 'Test',  'icon' => '✅'],
    ];
    $current = $steps[$step]['n'] ?? 1;
    $adjustMode = !empty($state['adjust_mode']);
    $editingBotId = $state['adjust_bot_id'] ?? null;
@endphp

<div class="mb-8">
    @if($adjustMode)
        <div class="mb-4 inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-amber-50 border border-amber-200 text-xs font-semibold text-amber-900">
            <span>✎</span>
            <span>Mod ajustare — modifici un agent existent</span>
        </div>
    @endif

    {{-- Step pills with progress line --}}
    <div class="flex items-center gap-1 mb-4">
        @foreach($steps as $slug => $meta)
            @php $isDone = $meta['n'] < $current; $isActive = $meta['n'] === $current; @endphp
            <div class="flex items-center flex-1 last:flex-initial">
                <div class="flex items-center gap-2">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full text-xs font-bold transition
                                {{ $isActive ? 'bg-coral text-white shadow-sm' : ($isDone ? 'bg-emerald-100 text-emerald-700 border border-emerald-200' : 'bg-cream text-muted border border-line') }}">
                        @if($isDone)
                            <span>✓</span>
                        @else
                            {{ $meta['n'] }}
                        @endif
                    </div>
                    <span class="hidden sm:inline text-xs font-medium
                                 {{ $isActive ? 'text-ink' : ($isDone ? 'text-emerald-700' : 'text-muted') }}">
                        {{ $meta['label'] }}
                    </span>
                </div>
                @if(!$loop->last)
                    <div class="flex-1 h-px mx-2 sm:mx-3 transition-colors
                                {{ $isDone ? 'bg-emerald-300' : 'bg-line' }}"></div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Mobile-only step counter (pills hide labels on small screens) --}}
    <div class="sm:hidden text-xs text-muted">
        Pasul {{ $current }} din {{ count($steps) }} · <span class="font-semibold text-ink">{{ $steps[$step]['label'] ?? '' }}</span>
    </div>
</div>
