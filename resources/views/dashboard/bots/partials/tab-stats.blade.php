    <div id="section-stats" class="mb-6">
        <div class="bg-white rounded-xl border border-line shadow-sm">
            <div class="px-5 py-4 border-b border-line flex items-center gap-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-coralsoft">
                    <svg class="w-4 h-4 text-coralh" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <h2 class="text-base font-semibold text-ink">Stats & Apeluri</h2>
            </div>
            <div class="p-5">
                {{-- Stat cards --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                    <div class="rounded-lg border border-line p-4 text-center">
                        <p class="text-2xl font-bold text-ink">{{ $callsThisMonth }}</p>
                        <p class="text-xs text-muted mt-1">Apeluri luna aceasta</p>
                    </div>
                    <div class="rounded-lg border border-line p-4 text-center">
                        <p class="text-2xl font-bold text-ink">{{ $avgDuration ? gmdate('i:s', (int) $avgDuration) : '---' }}</p>
                        <p class="text-xs text-muted mt-1">Durata medie</p>
                    </div>
                    <div class="rounded-lg border border-line p-4 text-center">
                        <p class="text-2xl font-bold text-ink">{{ $bot->calls_count ?? 0 }}</p>
                        <p class="text-xs text-muted mt-1">Total apeluri</p>
                    </div>
                </div>

                {{-- Recent calls table --}}
                @if($recentCalls->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-line">
                                    <th class="text-left px-4 py-3 text-xs font-medium text-muted uppercase tracking-wider">ID</th>
                                    <th class="text-left px-4 py-3 text-xs font-medium text-muted uppercase tracking-wider">Telefon</th>
                                    <th class="text-left px-4 py-3 text-xs font-medium text-muted uppercase tracking-wider">Durata</th>
                                    <th class="text-left px-4 py-3 text-xs font-medium text-muted uppercase tracking-wider">Status</th>
                                    <th class="text-left px-4 py-3 text-xs font-medium text-muted uppercase tracking-wider">Data</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach($recentCalls as $call)
                                    <tr class="hover:bg-cream transition-colors">
                                        <td class="px-4 py-3 font-mono text-xs text-muted">#{{ $call->id }}</td>
                                        <td class="px-4 py-3 text-inkSoft">{{ $call->phone_number ?? '---' }}</td>
                                        <td class="px-4 py-3 text-inkSoft">{{ $call->duration_seconds ? gmdate('i:s', $call->duration_seconds) : '---' }}</td>
                                        <td class="px-4 py-3">
                                            @php
                                                $statusClasses = [
                                                    'completed' => 'bg-green-50 text-green-700',
                                                    'failed' => 'bg-coralsoft text-coralh',
                                                    'in_progress' => 'bg-coralsoft text-coralh',
                                                    'missed' => 'bg-amber-50 text-amber-700',
                                                ];
                                                $statusLabels = [
                                                    'completed' => 'Finalizat',
                                                    'failed' => 'Esuat',
                                                    'in_progress' => 'In curs',
                                                    'missed' => 'Ratat',
                                                ];
                                            @endphp
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusClasses[$call->status] ?? 'bg-cream text-muted' }}">
                                                {{ $statusLabels[$call->status] ?? $call->status }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-muted text-xs">{{ $call->created_at->format('d.m.Y H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-6 text-sm text-muted">
                        Niciun apel inregistrat.
                    </div>
                @endif
            </div>
        </div>
    </div>