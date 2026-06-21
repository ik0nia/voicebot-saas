@extends('layouts.dashboard')

@section('title', 'Configurează agentul')

@section('content')
<div class="max-w-2xl mx-auto py-8 px-4">
    @include('dashboard.setup-wow._progress', ['step' => 'agent'])
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-ink">Agentul tău</h1>
        <p class="mt-2 text-muted">Dă-i un nume și alege un ton. Le poți schimba oricând.</p>
    </div>

    @if($errors->any())
        <div class="mb-4 p-3 bg-coralsoft border border-coral/30 rounded-lg text-sm text-coralh">
            @foreach($errors->all() as $err)<p>{{ $err }}</p>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('dashboard.setup-wow.saveAgent') }}" class="space-y-5 bg-white p-6 rounded-xl border border-line">
        @csrf
        <div>
            <label for="agent_name" class="block text-sm font-medium text-inkSoft">Nume agent</label>
            <input type="text" name="agent_name" id="agent_name" required maxlength="120"
                   value="{{ old('agent_name', ($state['business_name'] ?? '') . ' Asistent') }}"
                   class="mt-1.5 w-full rounded-lg border border-line px-3.5 py-2.5 text-sm">
        </div>

        <div>
            <label for="greeting" class="block text-sm font-medium text-inkSoft">Mesaj de întâmpinare <span class="text-muted">(opțional)</span></label>
            <textarea name="greeting" id="greeting" rows="2" maxlength="500"
                      placeholder="Salut! Cu ce te pot ajuta azi?"
                      class="mt-1.5 w-full rounded-lg border border-line px-3.5 py-2.5 text-sm">{{ old('greeting', '') }}</textarea>
        </div>

        <fieldset>
            <legend class="block text-sm font-medium text-inkSoft mb-2">Ton voce</legend>
            <div class="grid grid-cols-3 gap-2">
                @foreach([
                    'professional' => ['Profesionist', 'Clar, directă, fără gumesc'],
                    'friendly'     => ['Prietenos', 'Cald și relaxat'],
                    'empathetic'   => ['Empatic', 'Liniștit, atent la nuanțe'],
                ] as $key => $info)
                    <label class="cursor-pointer">
                        <input type="radio" name="voice_style" value="{{ $key }}" class="peer sr-only"
                               @if(old('voice_style', 'professional') === $key) checked @endif>
                        <div class="p-3 rounded-lg border-2 border-line peer-checked:border-coral peer-checked:bg-coralsoft transition-colors">
                            <p class="font-semibold text-sm text-ink">{{ $info[0] }}</p>
                            <p class="text-xs text-muted mt-0.5">{{ $info[1] }}</p>
                        </div>
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div class="flex justify-between pt-2">
            <a href="{{ route('dashboard.setup-wow.step', ['step' => 'website']) }}" class="text-sm text-muted hover:text-ink">← Înapoi</a>
            <button type="submit" class="px-6 py-2.5 rounded-lg bg-coral text-white text-sm font-semibold hover:bg-coralh">Creează și testează →</button>
        </div>
    </form>
</div>
@endsection
