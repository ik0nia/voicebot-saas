{{-- Sub-navigation shared by the three restaurant pages.
     $active is one of: orders | menu | settings --}}
<div class="flex flex-wrap items-center gap-2 mb-6">
    @php
        $links = [
            'orders'   => ['label' => '🧾 Comenzi',  'route' => 'dashboard.bots.restaurant.orders'],
            'menu'     => ['label' => '📖 Meniu',    'route' => 'dashboard.bots.restaurant.menu'],
            'settings' => ['label' => '⚙️ Livrare & comenzi', 'route' => 'dashboard.bots.restaurant.settings'],
        ];
    @endphp
    @foreach($links as $key => $link)
        <a href="{{ route($link['route'], $bot) }}"
           class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-sm font-medium border transition
                  {{ $active === $key
                        ? 'bg-coral text-white border-coral'
                        : 'bg-white text-inkSoft border-line hover:bg-cream' }}">
            {{ $link['label'] }}
        </a>
    @endforeach
</div>
