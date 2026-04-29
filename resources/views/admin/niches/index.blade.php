@extends('layouts.admin')

@section('title', 'Nișe')
@section('breadcrumb')<span class="text-slate-900 font-medium">Nișe</span>@endsection

@section('content')
@php
    $themeSwatch = [
        'red'     => 'bg-red-500',
        'emerald' => 'bg-emerald-500',
        'blue'    => 'bg-blue-500',
        'amber'   => 'bg-amber-500',
        'rose'    => 'bg-rose-500',
        'purple'  => 'bg-purple-500',
        'indigo'  => 'bg-indigo-500',
        'teal'    => 'bg-teal-500',
        'cyan'    => 'bg-cyan-500',
        'orange'  => 'bg-orange-500',
    ];
@endphp

<div class="space-y-6" data-csrf="{{ csrf_token() }}">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Nișe</h1>
            <p class="text-sm text-slate-500">Landing pages verticale pentru clienți potențiali</p>
        </div>
        <a href="{{ route('admin.niches.create') }}"
           class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Adaugă nișă
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
            <p class="text-sm font-medium text-emerald-800">{{ session('success') }}</p>
        </div>
    @endif

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-3 py-3 w-8"></th>
                    <th class="px-3 py-3 text-left">Slug</th>
                    <th class="px-3 py-3 text-left">Nume</th>
                    <th class="px-3 py-3 text-left">Vertical</th>
                    <th class="px-3 py-3 text-left">Temă</th>
                    <th class="px-3 py-3 text-left">Ton</th>
                    <th class="px-3 py-3 text-left w-20">Ordine</th>
                    <th class="px-3 py-3 text-left w-20">Activ</th>
                    <th class="px-3 py-3 text-right w-40">Acțiuni</th>
                </tr>
            </thead>
            <tbody id="nichesTbody" class="divide-y divide-slate-100">
                @forelse($niches as $n)
                    <tr data-id="{{ $n->id }}" class="hover:bg-slate-50">
                        <td class="px-3 py-3 text-slate-400 cursor-move select-none" title="Trage pentru a reordona">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </td>
                        <td class="px-3 py-3 font-mono text-xs text-slate-700">{{ $n->slug }}</td>
                        <td class="px-3 py-3 font-medium text-slate-900">{{ $n->name }}</td>
                        <td class="px-3 py-3 text-slate-600">{{ $n->vertical_label }}</td>
                        <td class="px-3 py-3">
                            <div class="flex items-center gap-2">
                                <span class="inline-block w-4 h-4 rounded-full {{ $themeSwatch[$n->color_theme] ?? 'bg-slate-300' }}"></span>
                                <span class="text-xs text-slate-600">{{ $n->color_theme }}</span>
                            </div>
                        </td>
                        <td class="px-3 py-3 text-xs text-slate-600">{{ $n->tone }}</td>
                        <td class="px-3 py-3 text-slate-600">{{ $n->sort_order }}</td>
                        <td class="px-3 py-3">
                            <button type="button"
                                    data-toggle-url="{{ route('admin.niches.toggle', $n) }}"
                                    class="toggle-active inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold {{ $n->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">
                                {{ $n->is_active ? 'Activ' : 'Inactiv' }}
                            </button>
                        </td>
                        <td class="px-3 py-3 text-right">
                            <div class="inline-flex items-center gap-1">
                                <a href="{{ route('new.niche', $n->slug) }}" target="_blank"
                                   class="px-2 py-1 text-xs rounded border border-slate-200 text-slate-600 hover:bg-slate-50">Vezi</a>
                                <a href="{{ route('admin.niches.edit', $n) }}"
                                   class="px-2 py-1 text-xs rounded border border-slate-200 text-slate-700 hover:bg-slate-50">Editează</a>
                                <form method="POST" action="{{ route('admin.niches.destroy', $n) }}"
                                      onsubmit="return confirm('Sigur vrei să ștergi nișa „{{ $n->name }}”?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2 py-1 text-xs rounded border border-red-200 text-red-600 hover:bg-red-50">Șterge</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-3 py-10 text-center text-slate-500">Nicio nișă încă. <a href="{{ route('admin.niches.create') }}" class="text-red-600 hover:underline">Adaugă prima</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $niches->links() }}</div>
</div>

<script>
(function () {
    const csrf = '{{ csrf_token() }}';

    document.querySelectorAll('.toggle-active').forEach(btn => {
        btn.addEventListener('click', async () => {
            const url = btn.dataset.toggleUrl;
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                });
                const j = await res.json();
                if (j.ok) {
                    btn.textContent = j.is_active ? 'Activ' : 'Inactiv';
                    btn.className = 'toggle-active inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold ' +
                        (j.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600');
                }
            } catch (e) { console.error(e); }
        });
    });

    // Simple drag-and-drop reorder
    const tbody = document.getElementById('nichesTbody');
    if (tbody) {
        let dragEl = null;
        tbody.querySelectorAll('tr[data-id]').forEach(tr => {
            tr.setAttribute('draggable', 'true');
            tr.addEventListener('dragstart', e => { dragEl = tr; tr.classList.add('opacity-50'); });
            tr.addEventListener('dragend', () => { tr.classList.remove('opacity-50'); saveOrder(); });
            tr.addEventListener('dragover', e => {
                e.preventDefault();
                if (!dragEl || dragEl === tr) return;
                const rect = tr.getBoundingClientRect();
                const after = (e.clientY - rect.top) > rect.height / 2;
                tr.parentNode.insertBefore(dragEl, after ? tr.nextSibling : tr);
            });
        });

        async function saveOrder() {
            const ids = [...tbody.querySelectorAll('tr[data-id]')].map(tr => tr.dataset.id);
            try {
                await fetch('{{ route("admin.niches.reorder") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body: JSON.stringify({ ids }),
                });
            } catch (e) { console.error(e); }
        }
    }
})();
</script>
@endsection
