@extends('layouts.dashboard')

@section('title', $isCreate ? 'Webhook nou' : 'Editează webhook')

@section('breadcrumb')
    <span class="text-muted">/</span>
    <a href="{{ route('dashboard.webhooks.index') }}" class="text-muted hover:text-inkSoft">Webhooks</a>
    <span class="text-muted">/</span>
    <span class="font-medium text-inkSoft">{{ $isCreate ? 'Nou' : $endpoint->name }}</span>
@endsection

@section('content')
<div class="max-w-2xl space-y-6">

    <h1 class="display text-3xl md:text-4xl font-semibold tracking-tight text-ink leading-none">
        {{ $isCreate ? 'Configurează un webhook' : 'Editează webhook' }}
    </h1>

    @if($errors->any())
        <div class="card p-4 border-coral/30 bg-coralsoft">
            <ul class="text-sm text-coralh space-y-1">
                @foreach($errors->all() as $err)<li>· {{ $err }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ $isCreate ? route('dashboard.webhooks.store') : route('dashboard.webhooks.update', $endpoint) }}"
          class="card p-6 space-y-6">
        @csrf
        @if(!$isCreate) @method('PUT') @endif

        <div>
            <label for="name" class="block text-sm font-medium text-inkSoft mb-1.5">Nume <span class="text-coral">*</span></label>
            <input type="text" name="name" id="name" required maxlength="100"
                   value="{{ old('name', $endpoint->name) }}"
                   placeholder="ex. CRM-ul nostru"
                   class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
            <p class="text-2xs text-muted mt-1">Nume intern pentru a-l recunoaște în listă.</p>
        </div>

        <div>
            <label for="url" class="block text-sm font-medium text-inkSoft mb-1.5">URL endpoint <span class="text-coral">*</span></label>
            <input type="url" name="url" id="url" required maxlength="500"
                   value="{{ old('url', $endpoint->url) }}"
                   placeholder="https://crm.exemplu.ro/sambla/webhook"
                   class="w-full rounded-lg border border-line bg-white px-4 py-2.5 text-sm font-mono focus:border-coral focus:ring-2 focus:ring-coral/20 outline-none">
            <p class="text-2xs text-muted mt-1">Sambla va face POST la acest URL pentru fiecare event selectat.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-inkSoft mb-2">Evenimente <span class="text-coral">*</span></label>
            <div class="space-y-2">
                @foreach(\App\Models\WebhookEndpoint::AVAILABLE_EVENTS as $ev => $label)
                    @php $checked = in_array($ev, old('events', $endpoint->events ?? []), true); @endphp
                    <label class="flex items-start gap-3 p-3 rounded-lg border border-line hover:bg-cream cursor-pointer">
                        <input type="checkbox" name="events[]" value="{{ $ev }}" {{ $checked ? 'checked' : '' }}
                               class="mt-0.5 w-4 h-4 rounded border-line text-coralh focus:ring-coral/20">
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-medium text-ink">{{ $label }}</div>
                            <div class="text-2xs text-muted mono mt-0.5">{{ $ev }}</div>
                        </div>
                    </label>
                @endforeach
            </div>
        </div>

        <div>
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1"
                       {{ old('is_active', $endpoint->is_active ?? true) ? 'checked' : '' }}
                       class="w-5 h-5 rounded border-line text-coralh focus:ring-coral/20">
                <div>
                    <span class="text-sm font-medium text-inkSoft">Activ</span>
                    <p class="text-2xs text-muted">Dacă debifezi, evenimentele nu se mai trimit.</p>
                </div>
            </label>
        </div>

        @if(!$isCreate)
            <div class="p-3 bg-cream rounded-lg text-2xs text-inkSoft border border-line">
                <strong>Notă:</strong> secretul HMAC nu poate fi văzut din nou după creare. Dacă l-ai pierdut, șterge webhook-ul și creează altul.
            </div>
        @endif

        <div class="flex items-center justify-between gap-3 pt-4 border-t border-line">
            <a href="{{ route('dashboard.webhooks.index') }}" class="text-sm text-muted hover:text-inkSoft">← Înapoi</a>
            <button type="submit" class="btn-coral rounded-pill px-5 py-2.5 text-sm font-medium">
                {{ $isCreate ? 'Salvează webhook' : 'Salvează modificările' }}
            </button>
        </div>
    </form>
</div>
@endsection
