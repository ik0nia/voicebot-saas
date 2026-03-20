@php
    $user = auth()->user();
    $onboarding = $user && $user->tenant_id
        ? app(\App\Services\OnboardingService::class)->getProgress($user->tenant_id)
        : null;
@endphp

@if($onboarding && !$onboarding['is_complete'])
<div id="onboarding-banner" class="mb-8 bg-gradient-to-r from-primary-50 to-white rounded-2xl border border-primary-100 p-6 lg:p-8 relative">
    {{-- Buton de închidere --}}
    <button onclick="document.getElementById('onboarding-banner').style.display='none'"
            class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 transition">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>

    <div class="flex flex-col lg:flex-row lg:items-start gap-6">
        <div class="flex-1">
            <h2 class="text-xl font-bold text-slate-900 mb-1">Bine ai venit! Hai să configurăm Sambla-ul tău</h2>
            <p class="text-sm text-slate-500 mb-4">Completează pașii de mai jos pentru a începe să folosești platforma.</p>

            {{-- Bară de progres --}}
            <div class="mb-6">
                <div class="flex items-center justify-between text-sm mb-1.5">
                    <span class="font-medium text-slate-700">Progres configurare</span>
                    <span class="font-semibold text-primary-600">{{ $onboarding['percentage'] }}%</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-2.5">
                    <div class="bg-primary-600 h-2.5 rounded-full transition-all duration-500"
                         style="width: {{ $onboarding['percentage'] }}%"></div>
                </div>
            </div>

            {{-- Lista de pași --}}
            <div class="space-y-3">
                @foreach($onboarding['steps'] as $step)
                <div class="flex items-start gap-3">
                    @if($step['completed'])
                        <div class="w-6 h-6 rounded-full bg-emerald-500 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-400 line-through">{{ $step['title'] }}</p>
                        </div>
                    @else
                        <div class="w-6 h-6 rounded-full border-2 border-slate-200 flex-shrink-0 mt-0.5"></div>
                        <div>
                            <a href="{{ $step['url'] }}" class="text-sm font-medium text-slate-900 hover:text-primary-600 transition">
                                {{ $step['title'] }}
                            </a>
                            <p class="text-xs text-slate-400 mt-0.5">{{ $step['description'] }}</p>
                        </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        {{-- Ilustrație --}}
        <div class="hidden lg:flex items-center justify-center w-48 flex-shrink-0">
            <svg viewBox="0 0 200 200" class="w-40 h-40 text-primary-200" fill="none">
                <circle cx="100" cy="100" r="80" fill="currentColor" opacity="0.3"/>
                <circle cx="100" cy="100" r="50" fill="currentColor" opacity="0.5"/>
                <path d="M85 75 L85 125 L125 100 Z" fill="currentColor" class="text-primary-500"/>
            </svg>
        </div>
    </div>
</div>
@endif
