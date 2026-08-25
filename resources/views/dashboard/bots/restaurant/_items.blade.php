{{-- One dish row, reused by the category blocks and the orphan block.
     Expects $items (Collection of MenuItem) and $bot. --}}
<div class="divide-y divide-line">
    @forelse($items as $item)
        <div class="flex items-start gap-3 px-4 py-3 hover:bg-cream/40 transition {{ $item->is_available ? '' : 'opacity-60' }}">
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-medium text-ink">{{ $item->name }}</span>
                    @unless($item->is_available)
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-2xs bg-stone-100 text-stone-600 border border-stone-300">indisponibil</span>
                    @endunless
                    @if($item->is_vegan)
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-2xs bg-emerald-50 text-emerald-700 border border-emerald-200">vegan</span>
                    @elseif($item->is_vegetarian)
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-2xs bg-emerald-50 text-emerald-700 border border-emerald-200">vegetarian</span>
                    @endif
                    @if($item->is_spicy)
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-2xs bg-coralsoft text-coralh border border-coral/30">picant</span>
                    @endif
                    @if($item->is_gluten_free)
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-2xs bg-blue-50 text-blue-700 border border-blue-200">fără gluten</span>
                    @endif
                </div>
                <div class="text-xs text-muted mt-0.5 space-x-2">
                    <span class="font-semibold text-coralh">{{ $item->formattedPrice() }}</span>
                    @if($item->portion)<span>· {{ $item->portion }}</span>@endif
                    @if($item->prep_time_minutes)<span>· {{ $item->prep_time_minutes }} min</span>@endif
                    @if(!empty($item->allergens))<span>· alergeni: {{ implode(', ', $item->allergens) }}</span>@endif
                </div>
                @if($item->description)
                    <p class="text-xs text-muted mt-1 line-clamp-2">{{ $item->description }}</p>
                @endif
                @if(empty($item->ingredients))
                    {{-- Deliberate, not an oversight: the voice prompt says
                         "nu am lista completă de ingrediente" rather than
                         inferring them from the dish name. --}}
                    <p class="text-2xs text-amber-700 mt-1">Fără ingrediente — agentul va spune că nu le are.</p>
                @else
                    <p class="text-2xs text-muted mt-1">Ingrediente: {{ implode(', ', $item->ingredients) }}</p>
                @endif
            </div>
            <div class="flex items-center gap-1 shrink-0">
                <form method="POST" action="{{ route('dashboard.bots.restaurant.item.toggle', [$bot, $item]) }}">
                    @csrf
                    <button type="submit"
                            class="text-xs px-2 py-1.5 rounded hover:bg-cream {{ $item->is_available ? 'text-muted hover:text-ink' : 'text-emerald-600' }}"
                            title="{{ $item->is_available ? 'Marchează indisponibil' : 'Marchează disponibil' }}">
                        {{ $item->is_available ? '⏸' : '▶' }}
                    </button>
                </form>
                <a href="{{ route('dashboard.bots.restaurant.item.edit', [$bot, $item]) }}"
                   class="text-xs text-muted hover:text-ink p-1.5 rounded hover:bg-cream" title="Editează">✎</a>
                <form method="POST" action="{{ route('dashboard.bots.restaurant.item.destroy', [$bot, $item]) }}"
                      onsubmit="return confirm('Ștergi „{{ addslashes($item->name) }}” din meniu?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs text-muted hover:text-coralh p-1.5 rounded hover:bg-coralsoft" title="Șterge">🗑</button>
                </form>
            </div>
        </div>
    @empty
        <div class="px-4 py-5 text-center text-xs text-muted">Nicio poziție în această categorie.</div>
    @endforelse
</div>
