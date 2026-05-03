@extends('layouts.dashboard')

@section('title', ($template ? 'Editează' : 'Creează') . ' template - ' . $channel->name)

@section('breadcrumb')
    <span class="text-muted">/</span>
    <a href="{{ route('dashboard.bots.channels.whatsapp-templates.index', [$bot, $channel]) }}" class="text-muted hover:text-inkSoft transition-colors">Template-uri</a>
    <span class="text-muted">/</span>
    <span class="font-medium text-inkSoft">{{ $template ? $template->name : 'Nou' }}</span>
@endsection

@section('content')
    @if($errors->any())
        <div class="mb-6 rounded-lg bg-coralsoft border border-coral/30 px-4 py-3 text-sm text-coralh">
            <ul class="space-y-1">
                @foreach($errors->all() as $error)
                    <li>• {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $isDraft = !$template || $template->isDraft();
        $action = $template
            ? route('dashboard.bots.channels.whatsapp-templates.update', [$bot, $channel, $template])
            : route('dashboard.bots.channels.whatsapp-templates.store', [$bot, $channel]);
        $components = $template->components ?? [];
        $headerComp = collect($components)->firstWhere('type', 'HEADER') ?? [];
        $bodyComp = collect($components)->firstWhere('type', 'BODY') ?? [];
        $footerComp = collect($components)->firstWhere('type', 'FOOTER') ?? [];
        $buttonsComp = collect($components)->firstWhere('type', 'BUTTONS') ?? ['buttons' => []];
    @endphp

    <form method="POST" action="{{ $action }}">
        @csrf
        @if($template)
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- LEFT: Basic info --}}
            <div class="space-y-6">
                <div class="rounded-xl border border-line bg-white p-5">
                    <h3 class="text-sm font-semibold text-ink mb-4">Informații de bază</h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-inkSoft mb-1.5" for="name">Nume template</label>
                            <input type="text" name="name" id="name" required pattern="[a-z0-9_]+" maxlength="64" {{ $isDraft ? '' : 'readonly' }}
                                   value="{{ old('name', $template->name ?? '') }}"
                                   class="w-full font-mono rounded-lg border border-line px-3 py-2 text-sm {{ $isDraft ? 'focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500' : 'bg-cream cursor-not-allowed' }}">
                            <p class="mt-1 text-xs text-muted">Doar litere mici, cifre, underscore. Ex: <code>booking_confirmation</code></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-inkSoft mb-1.5">Categorie</label>
                            <div class="space-y-1.5">
                                @foreach(['MARKETING' => 'Marketing — promoții, anunțuri', 'UTILITY' => 'Utilitate — confirmări, reminders, statusuri', 'AUTHENTICATION' => 'Autentificare — coduri OTP'] as $key => $label)
                                    <label class="flex items-start gap-2 p-2 rounded border border-line cursor-pointer hover:bg-cream {{ !$isDraft ? 'opacity-60 cursor-not-allowed' : '' }}">
                                        <input type="radio" name="category" value="{{ $key }}" required {{ !$isDraft ? 'disabled' : '' }}
                                               {{ old('category', $template->category ?? '') === $key ? 'checked' : '' }}
                                               class="mt-0.5">
                                        <span class="text-sm text-inkSoft">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-inkSoft mb-1.5" for="language">Limbă</label>
                            <select name="language" id="language" required {{ !$isDraft ? 'disabled' : '' }}
                                    class="w-full rounded-lg border border-line px-3 py-2 text-sm {{ !$isDraft ? 'bg-cream' : '' }}">
                                @foreach(['ro' => 'Română (ro)', 'ro_RO' => 'Română — RO (ro_RO)', 'en' => 'Engleză (en)', 'en_US' => 'Engleză — US (en_US)'] as $code => $label)
                                    <option value="{{ $code }}" {{ old('language', $template->language ?? 'ro') === $code ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                @if($template && !$isDraft)
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        <p class="font-medium mb-1">Template trimis la Meta</p>
                        <p class="text-xs">Structura nu mai poate fi modificată. Pentru schimbări, creează un template nou cu alt nume.</p>
                        @if($template->meta_template_id)
                            <p class="text-xs mt-2 font-mono">Meta ID: {{ $template->meta_template_id }}</p>
                        @endif
                    </div>
                @endif
            </div>

            {{-- CENTER: Composition --}}
            <div class="space-y-6">
                <div class="rounded-xl border border-line bg-white p-5">
                    <h3 class="text-sm font-semibold text-ink mb-4">Conținut</h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-inkSoft mb-1.5">Header (opțional)</label>
                            <select name="header_type" {{ !$isDraft ? 'disabled' : '' }}
                                    class="w-full rounded-lg border border-line px-3 py-2 text-sm mb-2 {{ !$isDraft ? 'bg-cream' : '' }}">
                                @foreach(['NONE' => 'Fără header', 'TEXT' => 'Text', 'IMAGE' => 'Imagine', 'VIDEO' => 'Video', 'DOCUMENT' => 'Document'] as $type => $label)
                                    <option value="{{ $type }}" {{ old('header_type', $headerComp['format'] ?? 'NONE') === $type ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <input type="text" name="header_text" maxlength="60" {{ !$isDraft ? 'readonly' : '' }}
                                   value="{{ old('header_text', $headerComp['text'] ?? '') }}"
                                   placeholder="Text header (max 60 char) — necesar doar dacă header type = TEXT"
                                   class="w-full rounded-lg border border-line px-3 py-2 text-sm {{ !$isDraft ? 'bg-cream' : '' }}">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-inkSoft mb-1.5" for="body_text">Body <span class="text-muted text-xs">(folosește {{ '{{1}} {{2}} ...' }} pentru variabile)</span></label>
                            <textarea name="body_text" id="body_text" required rows="6" maxlength="1024" {{ !$isDraft ? 'readonly' : '' }}
                                      class="w-full rounded-lg border border-line px-3 py-2 text-sm {{ !$isDraft ? 'bg-cream' : '' }}">{{ old('body_text', $bodyComp['text'] ?? '') }}</textarea>
                            <p class="mt-1 text-xs text-muted">Max 1024 caractere. Ex: <code>Bună {{ '{{1}}' }}, programarea ta pentru {{ '{{2}}' }} e confirmată.</code></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-inkSoft mb-1.5" for="footer_text">Footer (opțional)</label>
                            <input type="text" name="footer_text" id="footer_text" maxlength="60" {{ !$isDraft ? 'readonly' : '' }}
                                   value="{{ old('footer_text', $footerComp['text'] ?? '') }}"
                                   placeholder="Max 60 caractere"
                                   class="w-full rounded-lg border border-line px-3 py-2 text-sm {{ !$isDraft ? 'bg-cream' : '' }}">
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-line bg-white p-5">
                    <h3 class="text-sm font-semibold text-ink mb-4">Butoane (max 10)</h3>
                    <p class="text-xs text-muted mb-3">QUICK_REPLY trimite înapoi un postback. URL deschide un link. PHONE_NUMBER inițiază apel.</p>

                    <div class="space-y-3">
                        @for($i = 0; $i < 3; $i++)
                            @php $btn = $buttonsComp['buttons'][$i] ?? []; @endphp
                            <div class="grid grid-cols-12 gap-2 items-center">
                                <select name="buttons[{{ $i }}][type]" {{ !$isDraft ? 'disabled' : '' }}
                                        class="col-span-3 rounded-lg border border-line px-2 py-1.5 text-xs {{ !$isDraft ? 'bg-cream' : '' }}">
                                    <option value="">— gol —</option>
                                    @foreach(['QUICK_REPLY', 'URL', 'PHONE_NUMBER'] as $btnType)
                                        <option value="{{ $btnType }}" {{ old("buttons.{$i}.type", $btn['type'] ?? '') === $btnType ? 'selected' : '' }}>{{ $btnType }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="buttons[{{ $i }}][text]" maxlength="25" {{ !$isDraft ? 'readonly' : '' }}
                                       value="{{ old("buttons.{$i}.text", $btn['text'] ?? '') }}"
                                       placeholder="Text buton"
                                       class="col-span-4 rounded-lg border border-line px-2 py-1.5 text-xs {{ !$isDraft ? 'bg-cream' : '' }}">
                                <input type="text" name="buttons[{{ $i }}][url]" maxlength="2000" {{ !$isDraft ? 'readonly' : '' }}
                                       value="{{ old("buttons.{$i}.url", $btn['url'] ?? '') }}"
                                       placeholder="URL (dacă type=URL)"
                                       class="col-span-3 rounded-lg border border-line px-2 py-1.5 text-xs {{ !$isDraft ? 'bg-cream' : '' }}">
                                <input type="text" name="buttons[{{ $i }}][phone_number]" {{ !$isDraft ? 'readonly' : '' }}
                                       value="{{ old("buttons.{$i}.phone_number", $btn['phone_number'] ?? '') }}"
                                       placeholder="+40..."
                                       class="col-span-2 rounded-lg border border-line px-2 py-1.5 text-xs font-mono {{ !$isDraft ? 'bg-cream' : '' }}">
                            </div>
                        @endfor
                    </div>
                </div>
            </div>

            {{-- RIGHT: Variables + Preview --}}
            <div class="space-y-6">
                @if($template && !empty($template->bodyVariables()))
                    <div class="rounded-xl border border-line bg-white p-5">
                        <h3 class="text-sm font-semibold text-ink mb-4">Sample values pentru preview</h3>
                        @foreach($template->bodyVariables() as $varNum)
                            <div class="mb-3">
                                <label class="block text-xs font-medium text-muted mb-1">Variabila {{ '{{' . $varNum . '}}' }}</label>
                                <input type="text" name="sample_values[{{ $varNum }}]" maxlength="200"
                                       value="{{ old("sample_values.{$varNum}", $template->sample_values[$varNum] ?? '') }}"
                                       class="w-full rounded-lg border border-line px-3 py-2 text-sm">
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="rounded-xl border border-line bg-emerald-50/30 p-5">
                    <h3 class="text-sm font-semibold text-emerald-900 mb-3">Cum apare la utilizator</h3>
                    @php
                        $previewBody = $bodyComp['text'] ?? '';
                        if ($template && !empty($template->sample_values)) {
                            foreach ($template->sample_values as $num => $val) {
                                $previewBody = str_replace('{{' . $num . '}}', $val ?: '{{' . $num . '}}', $previewBody);
                            }
                        }
                    @endphp
                    <div class="rounded-lg bg-white border border-line p-4 max-w-xs ml-auto shadow-sm">
                        @if(!empty($headerComp['text']))
                            <p class="text-sm font-bold text-ink mb-1">{{ $headerComp['text'] }}</p>
                        @endif
                        @if($previewBody)
                            <p class="text-sm text-inkSoft whitespace-pre-line">{{ $previewBody }}</p>
                        @else
                            <p class="text-sm text-line italic">— body apare aici —</p>
                        @endif
                        @if(!empty($footerComp['text']))
                            <p class="text-xs text-muted mt-2">{{ $footerComp['text'] }}</p>
                        @endif
                        @foreach($buttonsComp['buttons'] ?? [] as $btn)
                            @if(!empty($btn['text']))
                                <button type="button" class="mt-2 w-full rounded border border-line px-3 py-1.5 text-xs text-emerald-700 font-medium">
                                    {{ $btn['text'] }}
                                </button>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-8 flex items-center justify-between border-t border-line pt-6">
            <a href="{{ route('dashboard.bots.channels.whatsapp-templates.index', [$bot, $channel]) }}" class="text-sm text-muted hover:text-inkSoft">Anulează</a>
            <div class="flex items-center gap-3">
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 transition-colors">
                    {{ $template ? 'Salvează modificările' : 'Salvează ca schiță' }}
                </button>
            </div>
        </div>
    </form>
@endsection
