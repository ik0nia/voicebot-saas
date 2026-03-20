@props(['title', 'description' => null])

<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900">{{ $title }}</h1>
    @if($description)
        <p class="mt-1 text-slate-500">{{ $description }}</p>
    @endif
    @if(isset($slot) && $slot->isNotEmpty())
        <div class="mt-4 flex items-center gap-3">
            {{ $slot }}
        </div>
    @endif
</div>
