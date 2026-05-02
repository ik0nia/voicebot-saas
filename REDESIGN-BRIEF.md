# Redesign Brief — Sambla Dashboard

## TL;DR
Construim un preview multi-pagină la `/preview/v2/*` care arată cum va arăta produsul redesignat ÎNAINTE să atingem un singur fișier real din `dashboard/**`. După ce userul validează preview-ul, începe migrarea reală pe componente.

**Decis:** baza vizuală = **Notion** (din `resources/views/preview/dashboard-notion.blade.php`) cu paletă Sambla din marketing (cream/ink-warm-brown/coral). Pe paginile operator-heavy (inbox, calls list, leads list) adoptăm pattern-uri de densitate **Linear** (din `resources/views/preview/dashboard-linear.blade.php`).

**Respins:** dashboard-stripe (prea generic), dashboard-stripe pur (paletă diferită de marketing).

## Context istoric

În sesiunea anterioară am rulat 15 audit-uri UI/UX paralele. Cele mai grave probleme găsite:

1. **Brand fork** dashboard ↔ marketing: dashboard folosește slate+red Tailwind defaults (~2,544 referințe slate-*), marketing folosește cream/ink/coral (token-urile oficiale). Utilizator logat = produs vizual diferit. ASTA E TEMA CENTRALĂ.
2. **Tenant-scope 404** fără escape hatch: super_admin lovește 404 sec pe `/dashboard/agenti/67/canale` (bot pe alt tenant). View-as widget e ascuns în dashboard layout, nu pe `/admin/tenanti/{id}` unde te-ai aștepta.
3. **Componente partajate inexistente**: 12 din 15 audit-uri cer aceleași 6 componente (`<x-button>`, `<x-input>`, `<x-empty-state>`, `<x-toast>`, `<x-modal>`, `<x-responsive-table>`). Nu există nicio.
4. **Critical security/payment**: parole plaintext în invitații, Twilio recording URLs leak credentiale, currency mix €/lei pe billing, settings endpoints fără role-gates, channels Meta rămân `pending` pe vecie (no webhook handler).

Lista completă cu 15 rapoarte detaliate e în istoricul conversației precedente; nu e nevoie să le re-rulezi.

## Design tokens (din memorie + tailwind.config.js)

```js
colors: {
  cream:  '#F5F1E8',  // background primar
  paper:  '#FFFFFF',  // card background
  ink:    '#1C1917',  // warm BROWN (NOT navy) — text principal
  inkSoft:'#3A3532',  // text secundar
  muted:  '#7B6F55',  // hints, captions
  line:   '#E8E3D7',  // borders subtle
  sand:   '#F5F1E8',  // sidebar bg
  coral:  '#DC2626',  // accent / primary action
  coralDark: '#991B1B', // hover/text-on-cream pentru AAA contrast
}
fontFamily: {
  sans: ['Inter', 'system-ui'],         // body
  display: ['Instrument Sans', 'serif'], // titluri H1-H3
}
borderRadius: {
  card: '24px',     // card standard
  primary: '48px',  // hero/primary cards
  pill: '999px',    // butoane (full pill, NU rounded-lg)
}
```

## Constrângeri de respectat (din `~/.claude/projects/-var-www-voicebot-saas/memory/`)

- **Terminologie:** „agenți AI" — niciodată „bot/voicebot/chatbot" în text user-facing. URL slug-uri: `agenti`, `canale`, `apeluri`, `transcrieri`, `mcp-servere`, `numere`, `setari`.
- **No vendor names pe marketing:** OpenAI, Twilio, Telnyx, ElevenLabs, pgvector, Stripe nu apar pe pagini publice. Pe dashboard intern OK.
- **No architecture reveals:** zero pipeline diagrams, chunk counts, embedding dimensions, "10 layers" copy.
- **Romanian native:** copy RO trebuie să sune nativ, nu traducere literală. Diacritice obligatorii.
- **Logo:** icon+text mereu împreună. Niciodată text fără icon.
- **Chat layout convention:** bot stânga (gri), client dreapta (coral). Conversație coerentă, nu monolog.
- **No emojis** in committed files unless explicit user request. Emoji icons în preview-uri OK pentru rapid prototyping, dar înlocuiește cu SVG la production.

## Scope: 12 pagini preview la `/preview/v2/*`

Toate static HTML+Tailwind+mock data. Zero controllers, zero DB, zero JS framework. Inter font + Instrument Sans din Google Fonts. Tailwind via CDN (deja pattern-ul folosit în preview-urile existente).

Pre-bar negru sus pe toate paginile cu link înapoi la index, exact ca preview-urile din `resources/views/preview/dashboard-*.blade.php`.

| # | Path | Conținut |
|---|---|---|
| 1 | `/preview/v2/` (index) | TOC navigabil cu thumbnail-uri pentru fiecare pagină |
| 2 | `/preview/v2/login` | Login redesignat în paleta cream/coral |
| 3 | `/preview/v2/onboarding` | Wizard 3 pași niche → agent → test (pe o pagină cu stepper) |
| 4 | `/preview/v2/dashboard` | Overview Notion-style (KPI tiles + chart + agent health + pipeline) |
| 5 | `/preview/v2/agents` | Listă agenți cu channel-presence chips + last activity + health donut |
| 6 | `/preview/v2/agent-edit` | Editor cu prompt + test panel inline (sticky right rail) |
| 7 | `/preview/v2/channel-connect` | WA wizard 4 pași cu screenshot-uri + „primul mesaj" celebration |
| 8 | `/preview/v2/inbox` | **3-pane Linear-density** (list + thread bot-stânga/client-dreapta + customer panel) |
| 9 | `/preview/v2/call-detail` | Waveform sincronizat cu transcript + sentiment heatmap deasupra |
| 10 | `/preview/v2/billing` | „Next bill" predicted card + plan selector cu monthly/yearly toggle |
| 11 | `/preview/v2/admin-tenant` | Tenant detail cu buton view-as inline (rezolvă pain-ul descoperit live) |
| 12 | `/preview/v2/error-404-tenant` | 404 branded cu CTA „Vezi ca [tenant]" |

## Cum se conectează la app real

Adaugă rutele în `routes/web.php` în grupul existent `/preview` (deja sunt `preview/dashboard/{stripe,notion,linear}` etc.). Pattern-ul:

```php
Route::prefix('preview/v2')->group(function () {
    Route::view('/', 'preview.v2.index');
    Route::view('/login', 'preview.v2.login');
    Route::view('/dashboard', 'preview.v2.dashboard');
    // ...
});
```

View-uri în `resources/views/preview/v2/*.blade.php`. NU folosi `@extends` din layouts existente — fiecare pagină self-contained ca preview-urile actuale, ca să nu fii constrâns de chrome-ul existent.

## Primul pas concret

Construiește în ordine:
1. `index.blade.php` cu TOC (cele 12 pagini ca cards cu thumbnail placeholder)
2. `dashboard.blade.php` (cea mai importantă pentru a stabili limbajul)
3. `inbox.blade.php` (pagina-vedetă, cea mai dificilă — dacă merge aici, merge oriunde)

După ce userul validează aceste 3, continui cu restul.

## Workflow așteptat

- Mic-mic. Un fișier per commit. Mesaj de commit în RO.
- NU `git add -A` — explicit paths only (per memory `feedback_git_add_explicit`).
- Nu menționa nume tenant în commit/comments (per memory `feedback_no_client_names_in_git`).
- Filesystem-ul `/var/www/voicebot-saas` e bind-mountat în prod container — orice fișier scris aici e LIVE. Pentru preview e OK pentru că e izolat la `/preview/v2/*`, dar verifică să nu rupi rute existente.
- După fiecare pagină, fă commit + push (deja în branch master, nu trebuie PR).

## Reference files (citește când ai nevoie)

- **CLAUDE.md** — arhitectura full
- **`resources/views/preview/dashboard-notion.blade.php`** — baza vizuală
- **`resources/views/preview/dashboard-linear.blade.php`** — pattern density pentru operator pages
- **`resources/views/new/home.blade.php`** + **`layouts/new.blade.php`** — paleta marketing canonică
- **`tailwind.config.js`** — token definitions
- **`resources/css/new.css`** — utility classes deja existente (`.btn-primary`, `.accent-text`, etc.)

## Open questions / decizii lăsate la userul

- Numele exact al companiei demo în mock data (folosesc „Dental Pro" ca în preview-urile existente, dacă nu confirmă altceva)
- Dacă preview-urile actuale (`/preview/dashboard/{stripe,notion,linear}`) rămân accesibile sau se șterg după validarea v2
- Ordinea de migrare reală (dashboard → inbox → bots → restul, sau alt ordin)

## Status la handoff (2026-05-02)

- Deploy E1-E7 LIVE pe prod (master = a11b121)
- Toate audit-urile UI/UX terminate, sintetizate
- Direcția aleasă: Notion base + Linear density operator pages, paletă Sambla
- Aștept user input pe primele 3 pagini după ce le construiesc
