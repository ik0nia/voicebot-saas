{{--
  OG image dinamic — 1200×630, theme warm Sambla.
  Folosit ca @section('og_image', route('new.og', [...])) pe paginile /new/*.
  Parametri primiti din route: $title (array cu 1-2 linii), $subtitle, $eyebrow, $accent.
--}}<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630" font-family="'Inter', system-ui, sans-serif">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#FAF7EF"/>
      <stop offset="1" stop-color="#F5F1E8"/>
    </linearGradient>
    <radialGradient id="glow" cx="0.85" cy="0.15" r="0.8">
      <stop offset="0" stop-color="{{ $accent }}" stop-opacity="0.22"/>
      <stop offset="0.7" stop-color="{{ $accent }}" stop-opacity="0"/>
    </radialGradient>
    <pattern id="diamond" width="48" height="48" patternUnits="userSpaceOnUse">
      <path d="M24 4 L32 12 L24 20 L16 12 Z" fill="{{ $accent }}" opacity="0.035"/>
    </pattern>
  </defs>

  {{-- Background --}}
  <rect width="1200" height="630" fill="url(#bg)"/>
  <rect width="1200" height="630" fill="url(#diamond)"/>
  <rect width="1200" height="630" fill="url(#glow)"/>

  {{-- Corner ornaments --}}
  <g opacity="0.6">
    <path d="M60 60 L100 60 M60 60 L60 100" stroke="{{ $accent }}" stroke-width="3" stroke-linecap="round"/>
    <path d="M1140 570 L1100 570 M1140 570 L1140 530" stroke="{{ $accent }}" stroke-width="3" stroke-linecap="round"/>
  </g>

  {{-- Eyebrow top-left (site / canonical) --}}
  <g transform="translate(80, 100)">
    <circle cx="6" cy="18" r="6" fill="{{ $accent }}"/>
    <text x="24" y="24" font-size="22" font-weight="600" fill="#1C1917" letter-spacing="2">{{ mb_strtoupper($eyebrow) }}</text>
  </g>

  {{-- Sambla wordmark bottom-left --}}
  <g transform="translate(80, 540)">
    <text x="0" y="0" font-size="36" font-style="italic" font-weight="500" fill="#1C1917" font-family="'Instrument Sans', 'Inter', serif">Sambla</text>
    <text x="0" y="28" font-size="14" fill="#78716C" letter-spacing="1">Agenți AI · Chat + Voce · Made in RO</text>
  </g>

  {{-- Accent bar decor bottom-right --}}
  <g transform="translate(1080, 540)">
    <rect x="0" y="0" width="40" height="4" fill="{{ $accent }}" rx="2"/>
    <rect x="50" y="0" width="20" height="4" fill="{{ $accent }}" rx="2" opacity="0.5"/>
    <text x="0" y="-8" font-size="12" fill="#78716C" letter-spacing="2">SAMBLA.RO</text>
  </g>

  {{-- Title + subtitle --}}
  <g transform="translate(80, 220)">
    @foreach($title as $i => $line)
      <text x="0" y="{{ 80 + $i * 88 }}" font-size="82" font-weight="600" fill="#1C1917" font-family="'Instrument Sans', 'Inter', serif">{{ $line }}</text>
    @endforeach
    <text x="0" y="{{ 110 + count($title) * 88 }}" font-size="28" font-weight="500" fill="#3A3532">{{ $subtitle }}</text>
  </g>

</svg>
