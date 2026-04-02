# Sambla Dashboard Design Specification
## Visual Identity Alignment with sambla.ro

**Objective:** Transform the dashboard from a generic white/slate Tailwind template into a branded experience that feels like a natural extension of the sambla.ro marketing site. The dashboard should feel warm, professional, culturally Romanian, and unmistakably "Sambla."

---

## 1. Color System

### 1.1 Primary Palette (from tailwind.config.js)

| Token | Hex | Usage |
|-------|-----|-------|
| primary-700 | #991b1b | Primary brand color, sidebar accents, active states |
| primary-500 | #dc2626 | Buttons, links, emphasis |
| primary-50 | #fef2f2 | Tinted card backgrounds, hover states |
| primary-100 | #fee2e2 | Badges, light fills |

### 1.2 Dark Palette (for sidebar, headers)

| Token | Hex | Usage |
|-------|-----|-------|
| slate-950 | #0f172a | Sidebar base background |
| slate-900 | #0f172a blended with red | Sidebar gradient end |
| Custom | linear-gradient(180deg, #0f172a 0%, #1a0a0a 100%) | Sidebar full gradient (slate-950 fading into a very dark red-black) |

### 1.3 Semantic Card Tint Colors

Each card type in the dashboard gets its own accent color. Never use plain white with border-slate-200 alone.

| Card Purpose | Background | Border | Icon Tint | Example |
|-------------|-----------|--------|-----------|---------|
| Bots / AI | bg-amber-50 | border-amber-200 | text-amber-600 | Bot stats, bot health |
| Conversations / Chat | bg-blue-50 | border-blue-200 | text-blue-600 | Message counts, recent chats |
| Leads / People | bg-emerald-50 | border-emerald-200 | text-emerald-600 | Lead pipeline, new leads |
| Commerce / Cart | bg-purple-50 | border-purple-200 | text-purple-600 | Add to cart, product clicks |
| Voice / Calls | bg-red-50 | border-red-200 | text-red-800 | Call counts, minutes used |
| Time / Usage | bg-sky-50 | border-sky-200 | text-sky-600 | Minutes, plan usage |
| Neutral / Settings | bg-slate-50 | border-slate-200 | text-slate-600 | Fallback for generic cards |

### 1.4 When to Use Each Color

- **Red (primary):** Anything brand-related -- CTAs, primary buttons, active nav items, the Sambla logo area, upgrade prompts, important badges
- **Blue:** Communication -- conversations, messages, chat-related stats and cards
- **Emerald/Green:** Success and people -- leads, won deals, successful actions, confirmation states
- **Amber/Yellow:** AI and intelligence -- bots, knowledge base health, auto-improvement suggestions, warnings
- **Purple:** Commerce -- cart events, product interactions, e-commerce features
- **Sky:** Time and measurement -- voice minutes, usage meters, analytics
- **Red-800 specifically:** The "power" red for sidebar active states, admin badges, the most important interactive elements

---

## 2. Sidebar Redesign

### 2.1 Background

**Current:** `bg-white border-r border-slate-200` -- plain, generic.

**New:** Dark gradient background matching the website's hero sections.

```
Background: linear-gradient(180deg, #0f172a 0%, #1a0808 100%)
```

This is slate-950 at the top, blending into a very dark warm red-black at the bottom. It matches the website's `bg-slate-950` hero sections.

Add a subtle Romanian motif overlay at 3-4% opacity covering the full sidebar, using the same diamond pattern SVG from the website hero (`#hero-motif` pattern). This gives cultural texture without being distracting.

### 2.2 Logo Section

- **Height:** h-16, matching current
- **Background:** Slightly lighter than sidebar body -- add `bg-white/[0.04]` overlay
- **Bottom border:** `border-b border-white/[0.08]` (not slate-200)
- **Logo:** Switch from `logo-light.svg` to `logo-dark.svg` (white version). If no white logo exists, add `brightness-0 invert` filter to the current logo.

### 2.3 Navigation Items

**Default (inactive) state:**
- Text: `text-slate-400` (not slate-600)
- Icon: `text-slate-500`
- Background: transparent
- Padding: `px-3 py-2.5`
- Border radius: `rounded-lg`
- Font: `text-sm font-medium`

**Hover state:**
- Text: `text-white`
- Icon: `text-white`
- Background: `bg-white/[0.06]`
- Transition: `transition-colors duration-150`

**Active state:**
- Text: `text-white font-semibold`
- Icon: `text-red-400`
- Background: `bg-red-900/40` (translucent deep red, not the current opaque bg-red-50)
- Left border: `border-l-[3px] border-red-400` (bright red accent line)
- Additional: subtle `shadow-sm shadow-red-900/20` for depth

**Remove** the current `pl-[9px]` padding hack for active items. Instead, use consistent padding and let the left border sit inside the padding naturally.

### 2.4 Section Separators

**Current:** `border-t border-slate-200` -- too visible on dark background.

**New:** `border-t border-white/[0.06]` -- subtle, barely visible line.

### 2.5 Section Labels (e.g., "Admin Platforma")

**Current:** `text-red-500 uppercase`

**New:** `text-red-400/70 uppercase tracking-wider text-[10px] font-bold` -- softer red that works on dark background.

### 2.6 Submenu (Transcrieri dropdown)

- Submenu border-left: `border-l-2 border-white/[0.08]` (not border-slate-200)
- Submenu items follow the same default/hover/active pattern as main nav
- Submenu items are slightly smaller: `text-[13px]`
- Chevron icon: `text-slate-500`, rotating as current

### 2.7 Admin Panel Button

**Current:** `bg-red-800 text-white` -- works but could be more distinguished.

**New:** `bg-gradient-to-r from-red-700 to-red-600 text-white` with `shadow-sm shadow-red-900/30`. Add a tiny shield emoji before the text: "🛡️ Admin Panel"

### 2.8 Bottom Section (Tenant Info)

**Current:** White background, border-t border-slate-200.

**New:**
- Background: `bg-white/[0.04]`
- Border: `border-t border-white/[0.08]`
- Tenant name: `text-white font-semibold text-sm`
- Plan badge colors on dark background:
  - Starter: `bg-slate-700/50 text-slate-300`
  - Professional: `bg-red-900/50 text-red-300`
  - Enterprise: `bg-amber-900/50 text-amber-300`
- "Upgrade" link: `text-red-400 hover:text-red-300` (not text-red-700 which is invisible on dark)

### 2.9 Mobile Sidebar Overlay

Keep `bg-slate-900/50` backdrop. The sidebar itself gets the same dark gradient treatment.

---

## 3. Topbar Redesign

### 3.1 Background

**Current:** `bg-white border-b border-slate-200`

**New:** Keep `bg-white` but add a 3px branded gradient strip at the very top of the topbar (or at the very top of the page above the topbar):

```
Top strip: h-[3px] bg-gradient-to-r from-primary-700 via-red-500 to-amber-500
```

This thin gradient bar is the first thing users see, immediately connecting the dashboard to the Sambla brand. The website uses this same red-to-amber gradient in its hero heading text.

### 3.2 Breadcrumb

Keep current styling. No changes needed.

### 3.3 User Menu & Notifications

Keep current styling. These are functional, not decorative.

---

## 4. Page Header Component

### 4.1 Dashboard Main Page Header

**Current:** Simple h1 + subtitle, no visual treatment.

**New:** Add a branded header area at the top of the main dashboard page.

Structure:
- Wrapper: `relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 to-slate-950 p-6 lg:p-8 mb-6`
- Romanian motif SVG overlay at 4% opacity (same as website hero)
- Gradient orb: `absolute -top-20 -right-20 w-60 h-60 bg-red-800/20 rounded-full blur-[80px]`
- Greeting (h1): `text-2xl font-bold text-white`
- Subtitle: `text-sm text-slate-400`
- This replaces the current plain `<div>` with the greeting

This creates a "mini hero" at the top of the dashboard that echoes the website's dark hero sections.

### 4.2 Sub-page Headers (Bots, Leads, etc.)

For interior pages, use the existing `<x-dashboard.page-header>` but enhance it:
- Add `<x-motif-divider>` below the header (the existing component, already built)
- Title: keep `text-2xl font-bold text-slate-900`
- Description: keep `text-slate-500`

---

## 5. Card Styling Guide

### 5.1 Standard Content Card

**Current:** `rounded-xl border border-slate-200 bg-white p-5 shadow-sm`

**New:** `rounded-2xl border bg-white p-5 shadow-sm hover:shadow-md transition-shadow duration-200`

The border color should match the card's semantic purpose (see Color System section 1.3). If the card has no specific purpose, use `border-slate-100` (lighter than current slate-200).

### 5.2 Stat Cards (Top Row on Dashboard)

**Current:** All identical -- `rounded-xl border border-slate-200 bg-white p-4 shadow-sm`

**New:** Each stat card gets its own accent color from the data (already partially implemented via the `$stat['bg']` variable).

Structure:
- Container: `rounded-2xl p-4 shadow-sm border transition-all hover:shadow-md`
- Background: Use the card's tint color as the FULL card background at a very light level: `bg-amber-50/50` (50% opacity of the -50 shade)
- Border: Use the corresponding -200 shade: `border-amber-200/60`
- Icon container: `rounded-xl` (current is rounded-lg) with the existing color classes
- Label: `text-[11px] font-semibold text-slate-500 uppercase tracking-wider` (add font-semibold)
- Value: `text-2xl font-bold text-slate-900` (keep)
- Sub-text: `text-[11px] text-slate-400` (keep)
- Add emoji before label text:
  - Bots: "🤖 Boti activi"
  - Conversations: "💬 Conversatii"
  - Leads: "👥 Leads noi"
  - Cart: "🛒 Adaugari cos"
  - Calls: "📞 Apeluri"
  - Minutes: "⏱️ Minute"

### 5.3 Quick Action Cards

**Current:** `rounded-xl border border-slate-200 bg-white p-4 shadow-sm`

**New:** Keep the existing hover-border color logic but enhance:
- Border radius: `rounded-2xl` (up from rounded-xl)
- Add `bg-gradient-to-br from-white to-{color}-50/30` for a very subtle color wash
- Icon container: `rounded-xl` (up from rounded-lg), size to `w-12 h-12` (up from w-10 h-10)
- Add emoji in title text:
  - "➕ Creeaza un bot"
  - "👥 Gestioneaza leads"
  - "🤝 Invita un coleg"

### 5.4 List Cards (Recent Conversations, Recent Leads)

**Current:** White with border-slate-200 header border.

**New:**
- Card: `rounded-2xl border border-slate-100 bg-white shadow-sm overflow-hidden`
- Header bar: `bg-slate-50/80` (very subtle gray, not white) with `border-b border-slate-100`
- Header title: Add emoji: "💬 Ultimele conversatii", "👥 Ultimele leads"
- Row hover: Keep `hover:bg-slate-50`
- Row borders: Keep `border-b border-slate-50`

### 5.5 Plan Usage Card

**Current:** White with border-slate-200.

**New:**
- Card: `rounded-2xl border border-primary-100 bg-gradient-to-br from-primary-50/30 to-white p-5 shadow-sm`
- This gives it a very subtle red tint, signaling it is a billing/plan-related element
- Plan badge: keep `bg-red-100 text-red-800`
- "Upgrade" link: keep `text-red-700 hover:underline`

### 5.6 Chart Card

**Current:** White with border-slate-200.

**New:**
- Card: `rounded-2xl border border-slate-100 bg-white p-5 shadow-sm`
- Title: Add emoji: "📊 Activitate -- 7 zile"
- No other changes needed; the chart itself provides color

---

## 6. Typography Guide

### 6.1 Font Family

`Inter` via Bunny Fonts. Already configured in tailwind.config.js. No changes.

### 6.2 Heading Hierarchy

| Element | Classes | Example |
|---------|---------|---------|
| Page title (h1) | `text-2xl font-bold text-slate-900` | "Dashboard", "Boti" |
| Dashboard hero title | `text-2xl font-bold text-white` | "Buna ziua, Codrut" (inside dark header) |
| Card title (h3) | `text-sm font-semibold text-slate-900` | "Activitate -- 7 zile" |
| Section label | `text-xs font-semibold text-slate-500 uppercase tracking-wider` | sidebar section headers |
| Stat label | `text-[11px] font-semibold text-slate-500 uppercase tracking-wider` | "CONVERSATII" |
| Stat value | `text-2xl font-bold text-slate-900` | "42" |
| Stat sub-text | `text-[11px] text-slate-400` | "128 mesaje azi" |
| Body text | `text-sm text-slate-600` | descriptions, explanations |
| Link text | `text-sm font-medium text-red-700 hover:text-red-900 hover:underline` | "Toate ->" |
| Small link | `text-xs text-red-700 hover:underline` | "Upgrade ->" |

### 6.3 Emoji Usage in Text

Emoji are used as inline label decorations, the way the website uses them in feature sections. Rules:

- Place emoji BEFORE the text with a space: "🤖 Boti activi" not "Boti activi 🤖"
- Use in: stat card labels, card titles, section headings, quick action titles, sidebar section labels
- Do NOT use in: breadcrumbs, body text, form labels, error messages, table headers
- Do NOT double up: one emoji per label maximum
- Preferred emoji by feature area:
  - Dashboard home: 🏠
  - Bots: 🤖
  - Conversations: 💬
  - Leads: 👥
  - Calls: 📞
  - Sites: 🌐
  - Knowledge base: 📚
  - Analytics: 📊
  - Commerce: 🛒
  - Team: 🤝
  - Settings: ⚙️
  - Billing: 💳
  - Voice: 🎙️
  - Callbacks/Scheduling: 📅
  - Opportunities: 🎯
  - Phone numbers: 📱

---

## 7. Button Styling Guide

### 7.1 Primary Button

**Current:** `bg-red-800 text-white rounded-lg hover:bg-red-900`

**New:** Match the website's CTA style.
- `bg-gradient-to-r from-red-700 to-red-600 text-white font-semibold rounded-xl hover:from-red-600 hover:to-red-500 transition-all duration-200 shadow-md shadow-red-700/20`
- Padding: `px-5 py-2.5` for standard, `px-8 py-4` for hero-level CTAs
- Active state: `active:scale-[0.98]`
- Add the arrow icon on primary action buttons, as the website does:
  ```
  <svg class="w-4 h-4 transition-transform group-hover:translate-x-1">arrow</svg>
  ```

### 7.2 Secondary Button

- `bg-white text-slate-700 font-medium rounded-xl border border-slate-200 hover:bg-slate-50 hover:border-slate-300 transition-all duration-200 shadow-sm`
- Same padding as primary

### 7.3 Ghost/Text Button

- `text-red-700 font-medium hover:text-red-900 hover:underline`
- No background, no border
- Used for "Upgrade ->", "Toate ->", "Nu mai arata" links

### 7.4 Danger Button

- `bg-red-50 text-red-700 font-medium rounded-xl border border-red-200 hover:bg-red-100 transition-colors`
- For destructive actions (delete bot, remove team member)

### 7.5 Button Sizes

| Size | Classes |
|------|---------|
| Small | `px-3 py-1.5 text-xs rounded-lg` |
| Default | `px-5 py-2.5 text-sm rounded-xl` |
| Large | `px-8 py-3.5 text-base rounded-xl` |

---

## 8. Romanian Motif Integration Guide

### 8.1 Available Components

Two motif components already exist:

1. **`<x-motif-border>`** -- Full-width horizontal SVG border with cross/rhombus pattern. 16px tall. Accepts `color` prop.
2. **`<x-motif-divider>`** -- Centered diamond ornament with gradient lines extending left and right. Uses primary colors.
3. **`<x-hero-ornament>`** -- Bihor/Ardeal inspired centered ornament. Larger than motif-divider.

### 8.2 Where to Use Motifs in the Dashboard

| Location | Component | Color | Notes |
|----------|-----------|-------|-------|
| Dashboard hero header (dark) | Inline SVG pattern | #991b1b at 3-4% opacity | Full background overlay, same as website hero |
| Sidebar background | Inline SVG pattern | white at 3% opacity | Subtle texture, diamond pattern |
| Between major dashboard sections | `<x-motif-divider>` | primary (default) | Use sparingly -- only between the stats row and the chart/pipeline row, and between the chart row and the recent activity row |
| Bot page header area | `<x-motif-border>` | primary-200 | Below the bot header, above the tab bar |
| Empty states | `<x-hero-ornament>` | primary | Above "no data" messages for visual warmth |

### 8.3 Where NOT to Use Motifs

- Inside cards (too busy)
- In forms (distracting)
- In table views (cluttered)
- On every single page section (overuse kills the effect)

### 8.4 Sidebar Motif Specifics

The sidebar gets the same diamond pattern used in the website hero, but rendered in white at very low opacity:

```
Pattern: diamond/rhombus grid, 80x80px tile
Fill: white
Opacity: 0.03 (3%)
Positioning: absolute inset-0, behind all sidebar content
```

This means the sidebar has: dark gradient background + motif overlay + navigation content (in that z-order).

---

## 9. Specific Page Treatments

### 9.1 Dashboard Index (`/dashboard`)

1. **Dark hero header** (Section 4.1) with greeting, motif overlay, gradient orb
2. **Action items** (keep existing styling, already has color)
3. `<x-motif-divider class="my-2">` between action items and stats
4. **Stat cards** with individual colors and emoji labels (Section 5.2)
5. **Bot health cards** (keep, already colorful)
6. `<x-motif-divider class="my-2">` between stats row and plan usage
7. **Plan usage** with subtle red tint (Section 5.5)
8. **Chart + Pipeline** -- chart card neutral, pipeline card with emerald accents for "won" stage
9. `<x-motif-divider class="my-2">` between chart row and recent activity
10. **Recent activity** cards with emoji titles (Section 5.4)
11. **Quick actions** with enhanced colors and emoji (Section 5.3)

### 9.2 Bot Detail Page (`/dashboard/boti/{id}`)

1. Bot header partial -- keep the existing gradient treatment if it has one
2. Add `<x-motif-border color="primary-200">` between header and tab bar
3. Tab bar -- keep current styling (red-800 active, slate-500 inactive). It already matches the brand.
4. Tab content cards -- apply Section 5.1 styling

### 9.3 List Pages (Leads, Conversations, Sites, etc.)

1. Page header with `<x-dashboard.page-header>` + emoji in title
2. `<x-motif-divider>` below header
3. Filters/search area -- neutral bg-white card
4. Table/list card -- `rounded-2xl border border-slate-100 bg-white shadow-sm`

---

## 10. Onboarding Banner

**Current:** `border border-primary-100 bg-gradient-to-br from-primary-50 to-white`

**New:** Enhance to match the website's "warm welcome" feeling:
- `rounded-2xl border border-primary-200 bg-gradient-to-br from-primary-50 via-white to-amber-50/30 p-6 shadow-sm`
- Add a small Romanian motif ornament in the top-right corner at 5% opacity
- Title: "👋 Bine ai venit! Configureaza Sambla-ul tau"
- Checkmark color: keep `text-emerald-500`
- Empty circle: keep `border-slate-300`
- Step link color: keep `text-primary-600`

---

## 11. Shadow System

| Token | Value | Usage |
|-------|-------|-------|
| shadow-sm | Default Tailwind | Cards at rest |
| shadow-md | Default Tailwind | Cards on hover |
| shadow-lg shadow-red-700/10 | Red-tinted large shadow | Primary buttons |
| shadow-xl shadow-red-100/50 | Red-tinted XL shadow | Feature cards on hover (matching website) |

---

## 12. Border Radius System

| Element | Radius | Class |
|---------|--------|-------|
| Cards | 16px | `rounded-2xl` |
| Buttons | 12px | `rounded-xl` |
| Input fields | 12px | `rounded-xl` |
| Badges/pills | 9999px | `rounded-full` |
| Icon containers | 12px | `rounded-xl` |
| Sidebar nav items | 8px | `rounded-lg` |
| Small thumbnails | 8px | `rounded-lg` |

The website uses `rounded-2xl` (16px) for all cards. The dashboard currently uses `rounded-xl` (12px). Upgrade all cards to `rounded-2xl` to match.

---

## 13. Transition & Interaction Guide

- All hover transitions: `transition-all duration-200` or `transition-colors duration-150`
- Card hover: lift shadow from shadow-sm to shadow-md
- Button hover: gradient shift (lighter), `group-hover:translate-x-1` for arrow icons
- Button active: `active:scale-[0.98]` subtle press effect
- Nav item hover: background fade in at 150ms
- No bouncing, no spinning, no excessive animation. Keep it professional.

---

## 14. Summary: Before and After

| Element | Before | After |
|---------|--------|-------|
| Sidebar | White, border-slate-200 | Dark gradient + motif overlay |
| Sidebar text | slate-600 / red-800 active | slate-400 / white active with red-400 accent |
| Cards | All white, border-slate-200 | Color-tinted by purpose, border matching |
| Card radius | rounded-xl | rounded-2xl |
| Stat cards | Identical white boxes | Each with unique accent color + emoji |
| Buttons | flat bg-red-800 | Gradient from-red-700 to-red-600 with shadow |
| Headings | No emoji | Emoji prefixes on card titles and labels |
| Section dividers | None | `<x-motif-divider>` between major sections |
| Dashboard header | Plain white background | Dark gradient mini-hero with motif |
| Motifs | Exist in components but unused in dashboard | Sidebar texture, header overlay, section dividers |
| Page background | bg-slate-50 | bg-slate-50 (keep -- provides contrast with dark sidebar) |

---

## 15. Implementation Priority

1. **Sidebar** -- Highest visual impact. Single file change (`layouts/dashboard.blade.php`).
2. **Dashboard index stat cards** -- Add emoji, color tints. Single file (`dashboard/index.blade.php`).
3. **Dashboard dark hero header** -- Add branded greeting area. Single file.
4. **Motif dividers** -- Drop `<x-motif-divider>` between dashboard sections. Single file.
5. **Card border-radius upgrade** -- Global find-replace `rounded-xl` to `rounded-2xl` on cards.
6. **Button gradient upgrade** -- Update primary buttons across all dashboard views.
7. **List card emoji titles** -- Add emoji to card headings across dashboard pages.
8. **Onboarding banner enhancement** -- Subtle color and emoji improvements.
