@extends('layouts.dashboard')

@section('title', 'Echipă')

@section('breadcrumb')
    <span class="text-slate-400">/</span>
    <span class="font-medium text-slate-700">Echipă</span>
@endsection

@section('content')
<div class="space-y-6">

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="flex items-center gap-3 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="flex items-center gap-3 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Echipă</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $members->count() }} {{ $members->count() === 1 ? 'membru' : 'membri' }} în echipă</p>
        </div>
        <button onclick="toggleInviteForm()"
                id="invite-toggle-btn"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-900 transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Invită membru
        </button>
    </div>

    {{-- Invite Form (hidden by default) --}}
    <div id="invite-form" class="hidden">
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-900">Invită un membru nou</h2>
                <p class="mt-1 text-sm text-slate-500">Trimite o invitație prin email cu datele de acces.</p>
            </div>
            <form method="POST" action="/dashboard/echipa/invite" class="p-5">
                @csrf
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    {{-- Nume --}}
                    <div>
                        <label for="invite-name" class="block text-sm font-medium text-slate-700 mb-1.5">Nume</label>
                        <input type="text" name="name" id="invite-name" value="{{ old('name') }}" required
                               placeholder="ex. Maria Popescu"
                               class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 placeholder-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition" />
                        @error('name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label for="invite-email" class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                        <input type="email" name="email" id="invite-email" value="{{ old('email') }}" required
                               placeholder="ex. maria@companie.ro"
                               class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 placeholder-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition" />
                        @error('email')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Rol --}}
                    <div class="sm:col-span-2">
                        <label for="invite-role" class="block text-sm font-medium text-slate-700 mb-1.5">Rol</label>
                        <select name="role" id="invite-role" required
                                class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition">
                            <option value="tenant_admin" {{ old('role') === 'tenant_admin' ? 'selected' : '' }}>Administrator</option>
                            <option value="tenant_manager" {{ old('role', 'tenant_manager') === 'tenant_manager' ? 'selected' : '' }}>Manager</option>
                            <option value="tenant_viewer" {{ old('role') === 'tenant_viewer' ? 'selected' : '' }}>Vizualizator</option>
                        </select>
                        @error('role')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror

                        {{-- Role descriptions --}}
                        <div class="mt-3 space-y-1.5">
                            <div class="flex items-start gap-2 text-xs text-slate-500">
                                <span class="inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 font-medium text-red-800 shrink-0">Admin</span>
                                <span>Acces complet la toate funcționalitățile</span>
                            </div>
                            <div class="flex items-start gap-2 text-xs text-slate-500">
                                <span class="inline-flex items-center rounded-full bg-sky-50 px-2 py-0.5 font-medium text-sky-700 shrink-0">Manager</span>
                                <span>Poate gestiona boți și apeluri, fără acces la facturare</span>
                            </div>
                            <div class="flex items-start gap-2 text-xs text-slate-500">
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 font-medium text-slate-600 shrink-0">Vizualizator</span>
                                <span>Doar vizualizare, fără modificări</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-3 mt-6 pt-5 border-t border-slate-200">
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-900 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        Trimite invitația
                    </button>
                    <button type="button" onclick="toggleInviteForm()"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        Anulează
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Team Table --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/50">
                        <th class="px-5 py-3 font-medium text-slate-500">Membru</th>
                        <th class="px-5 py-3 font-medium text-slate-500">Rol</th>
                        <th class="px-5 py-3 font-medium text-slate-500 hidden sm:table-cell">Ultima activitate</th>
                        <th class="px-5 py-3 font-medium text-slate-500 text-right">Acțiuni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($members as $member)
                        @php
                            $initials = collect(explode(' ', $member->name))->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->join('');
                            $role = $member->roles->first()?->name ?? 'tenant_viewer';
                            $roleLabels = [
                                'tenant_admin' => 'Administrator',
                                'tenant_manager' => 'Manager',
                                'tenant_viewer' => 'Vizualizator',
                            ];
                            $roleBadgeColors = [
                                'tenant_admin' => 'bg-red-50 text-red-800',
                                'tenant_manager' => 'bg-sky-50 text-sky-700',
                                'tenant_viewer' => 'bg-slate-100 text-slate-600',
                            ];
                            $isCurrentUser = $member->id === auth()->id();
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            {{-- Membru --}}
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                                        <span class="text-xs font-semibold text-red-800">{{ $initials }}</span>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-slate-900 truncate">
                                            {{ $member->name }}
                                            @if($isCurrentUser)
                                                <span class="text-xs font-normal text-slate-400">(tu)</span>
                                            @endif
                                        </p>
                                        <p class="text-xs text-slate-500 truncate">{{ $member->email }}</p>
                                    </div>
                                </div>
                            </td>

                            {{-- Rol --}}
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $roleBadgeColors[$role] ?? $roleBadgeColors['tenant_viewer'] }}">
                                    {{ $roleLabels[$role] ?? 'Vizualizator' }}
                                </span>
                            </td>

                            {{-- Ultima activitate --}}
                            <td class="px-5 py-4 hidden sm:table-cell">
                                <span class="text-sm text-slate-500">
                                    @if($member->last_login_at)
                                        {{ \Carbon\Carbon::parse($member->last_login_at)->diffForHumans() }}
                                    @elseif($member->updated_at)
                                        {{ $member->updated_at->diffForHumans() }}
                                    @else
                                        —
                                    @endif
                                </span>
                            </td>

                            {{-- Acțiuni --}}
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    {{-- Change role --}}
                                    @if(!$isCurrentUser)
                                        <form method="POST" action="/dashboard/echipa/{{ $member->id }}/role" class="flex items-center">
                                            @csrf
                                            @method('PATCH')
                                            <select name="role" onchange="this.form.submit()"
                                                    class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-700 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition">
                                                <option value="tenant_admin" {{ $role === 'tenant_admin' ? 'selected' : '' }}>Administrator</option>
                                                <option value="tenant_manager" {{ $role === 'tenant_manager' ? 'selected' : '' }}>Manager</option>
                                                <option value="tenant_viewer" {{ $role === 'tenant_viewer' ? 'selected' : '' }}>Vizualizator</option>
                                            </select>
                                        </form>

                                        {{-- Remove --}}
                                        <form method="POST" action="/dashboard/echipa/{{ $member->id }}/remove"
                                              onsubmit="return confirm('Ești sigur că vrei să elimini pe {{ $member->name }}? Această acțiune este ireversibilă.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Elimină membrul"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-red-200 bg-white text-red-400 hover:bg-red-50 hover:text-red-600 transition-colors">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-slate-400 italic">—</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($members->isEmpty())
            <div class="px-5 py-12 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
                    <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <h4 class="mt-3 text-sm font-medium text-slate-900">Niciun membru în echipă</h4>
                <p class="mt-1 text-sm text-slate-500">Invită primul membru pentru a colabora.</p>
            </div>
        @endif
    </div>

    {{-- Permissions Reference (collapsible) --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <button onclick="togglePermissions()" type="button"
                class="flex items-center justify-between w-full px-5 py-4 text-left">
            <div>
                <h3 class="text-base font-semibold text-slate-900">Referință permisiuni</h3>
                <p class="mt-0.5 text-sm text-slate-500">Ce poate face fiecare rol</p>
            </div>
            <svg id="permissions-chevron" class="w-5 h-5 text-slate-400 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div id="permissions-table" class="hidden border-t border-slate-200">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/50">
                            <th class="px-5 py-3 font-medium text-slate-500">Permisiune</th>
                            <th class="px-5 py-3 font-medium text-slate-500 text-center">Administrator</th>
                            <th class="px-5 py-3 font-medium text-slate-500 text-center">Manager</th>
                            <th class="px-5 py-3 font-medium text-slate-500 text-center">Vizualizator</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @php
                            $permissions = [
                                ['label' => 'Gestiune boți', 'admin' => true, 'manager' => true, 'viewer' => false],
                                ['label' => 'Vizualizare apeluri', 'admin' => true, 'manager' => true, 'viewer' => true],
                                ['label' => 'Setări cont', 'admin' => true, 'manager' => false, 'viewer' => false],
                                ['label' => 'Facturare', 'admin' => true, 'manager' => false, 'viewer' => false],
                                ['label' => 'Invitare membri', 'admin' => true, 'manager' => true, 'viewer' => false],
                            ];
                        @endphp
                        @foreach($permissions as $perm)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-5 py-3 font-medium text-slate-700">{{ $perm['label'] }}</td>
                                <td class="px-5 py-3 text-center">
                                    @if($perm['admin'])
                                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-green-100 text-green-600">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-50 text-red-400">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-center">
                                    @if($perm['manager'])
                                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-green-100 text-green-600">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-50 text-red-400">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-center">
                                    @if($perm['viewer'])
                                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-green-100 text-green-600">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-50 text-red-400">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function toggleInviteForm() {
        var form = document.getElementById('invite-form');
        var isHidden = form.classList.contains('hidden');
        if (isHidden) {
            form.classList.remove('hidden');
            document.getElementById('invite-name').focus();
        } else {
            form.classList.add('hidden');
        }
    }

    function togglePermissions() {
        var table = document.getElementById('permissions-table');
        var chevron = document.getElementById('permissions-chevron');
        var isHidden = table.classList.contains('hidden');
        if (isHidden) {
            table.classList.remove('hidden');
            chevron.classList.add('rotate-180');
        } else {
            table.classList.add('hidden');
            chevron.classList.remove('rotate-180');
        }
    }

    // Auto-show invite form if there are validation errors
    @if($errors->any())
        document.addEventListener('DOMContentLoaded', function() {
            toggleInviteForm();
        });
    @endif
</script>
@endpush
