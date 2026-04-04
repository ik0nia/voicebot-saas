@extends('layouts.admin')

@section('title', 'Programare Social Media')
@section('breadcrumb')
    <a href="{{ route('admin.social.index') }}" class="text-slate-400 hover:text-slate-600">Social Media</a>
    <span class="mx-1.5 text-slate-300">/</span>
    Programare
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
            <h1 class="text-2xl font-bold text-slate-900">Programare Publicari</h1>
            <p class="text-sm text-slate-500 mt-1">Configureaza autopilotul pentru fiecare platforma</p>
        </div>
    </div>

    @php
        $platformConfigs = [
            'facebook' => ['label' => 'Facebook', 'color' => 'blue'],
            'instagram' => ['label' => 'Instagram', 'color' => 'pink'],
            'blog' => ['label' => 'Blog', 'color' => 'slate'],
        ];
    @endphp

    @foreach($platformConfigs as $platformKey => $pConfig)
        @php $schedule = $schedules[$platformKey] ?? null; @endphp
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <form method="POST" action="{{ route('admin.social.schedule.save') }}">
                @csrf
                <input type="hidden" name="platform" value="{{ $platformKey }}">

                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-semibold text-slate-900">{{ $pConfig['label'] }}</h2>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" {{ $schedule?->is_active ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-red-600"></div>
                        <span class="ml-2 text-sm font-medium text-slate-700">Activ</span>
                    </label>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Postari pe zi</label>
                        <input type="number" name="posts_per_day" value="{{ $schedule?->posts_per_day ?? 1 }}" min="1" max="5"
                               class="w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Ore de publicare</label>
                        <input type="text" name="posting_times"
                               value="{{ is_array($schedule?->posting_times) ? implode(', ', $schedule->posting_times) : ($schedule?->posting_times ?? '10:00') }}"
                               placeholder="09:00, 14:00, 19:00"
                               class="w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500">
                        <p class="text-xs text-slate-400 mt-1">Separate prin virgula (HH:MM)</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Subiecte</label>
                        <input type="text" name="topics"
                               value="{{ is_array($schedule?->topics) ? implode(', ', $schedule->topics) : ($schedule?->topics ?? '') }}"
                               placeholder="chatboti, AI, automatizare"
                               class="w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500">
                        <p class="text-xs text-slate-400 mt-1">Separate prin virgula</p>
                    </div>
                </div>

                @if($platformKey === 'blog')
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4 p-4 bg-slate-50 rounded-lg">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Frecventa articole (zile)</label>
                            <input type="number" name="blog_frequency_days" value="{{ $schedule?->blog_frequency_days ?? 3 }}" min="1" max="30"
                                   class="w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500">
                        </div>
                        <div class="flex items-end">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="hidden" name="auto_blog" value="0">
                                <input type="checkbox" name="auto_blog" value="1" {{ $schedule?->auto_blog ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-red-600"></div>
                                <span class="ml-2 text-sm font-medium text-slate-700">Auto-generare articole blog</span>
                            </label>
                        </div>
                    </div>
                @else
                    <input type="hidden" name="auto_blog" value="0">
                    <input type="hidden" name="blog_frequency_days" value="3">
                @endif

                {{-- Style guidelines preview --}}
                @if($schedule?->style_guidelines)
                    <div class="mb-4 p-4 bg-slate-50 rounded-lg">
                        <h3 class="text-sm font-semibold text-slate-700 mb-1">Ghid de stil (auto-generat)</h3>
                        <p class="text-xs text-slate-500">
                            @if(is_array($schedule->style_guidelines))
                                {{ implode(' | ', array_slice($schedule->style_guidelines, 0, 5)) }}
                                @if(count($schedule->style_guidelines) > 5)
                                    ... (+{{ count($schedule->style_guidelines) - 5 }} mai mult)
                                @endif
                            @else
                                {{ \Illuminate\Support\Str::limit((string)$schedule->style_guidelines, 200) }}
                            @endif
                        </p>
                    </div>
                @endif

                <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Salveaza {{ $pConfig['label'] }}
                </button>
            </form>
        </div>
    @endforeach
</div>
@endsection
