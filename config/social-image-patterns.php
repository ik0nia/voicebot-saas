<?php

/*
 | Social Media Image Pattern Catalog
 | ----------------------------------
 | Each pattern is a reusable visual grammar (bento grid, annotated portrait,
 | split-screen, etc.) that the SocialImageOrchestrator renders into a final
 | prompt for gpt-image-2. Patterns are niche-agnostic; niche-specific subject
 | hints and copy are injected at render time.
 |
 | Design language locked in `brand_preamble` matches the live sambla.ro site
 | (tailwind.config.js + resources/css/new.css). Update both sides together.
 */

return [

    'brand_preamble' => <<<'BRAND_PREAMBLE'
BRAND DESIGN LANGUAGE (must match the Sambla website exactly):
- Base palette: Cream #F5F1E8 (primary background), Paper #FAF7EF (secondary), Sand #EFE5D0 (tertiary).
- Text/ink: Ink #1C1917 (warm dark brown — NEVER pure black, NEVER navy blue), Ink Soft #3A3532 for muted text.
- Primary accent: Coral Red #DC2626, with Dark Red #991B1B used only for depth in gradients.
- Secondary glows: soft peach #F2E59A and soft lilac #C7B8E8, only as low-opacity radial gradients in backgrounds.
- Typography: display headings in Instrument Sans, medium weight 500 (never bold), tight letter-spacing around -0.02em. Body in Inter, 400-600. Small caps / labels in JetBrains Mono, wide tracking, muted color.
- Surfaces: cards with large rounded corners (48px radius), soft shadow (like box-shadow 0 20px 40px -10px rgba(0,0,0,0.08)), no hard borders.
- Signature accents: a coral ✦ symbol used as list bullet; a ◇ folk diamond in section headers; an emerald green #10B981 live-ping dot for status indicators; emerald checkmarks.
- Subtle film grain overlay on photographic content, very low intensity.

TONE: warm, Romanian-local, human-first, premium but approachable. NEVER generic SaaS stock, NEVER futuristic neon/cyber, NEVER dark dystopian backgrounds, NEVER corporate clinical.
BRAND_PREAMBLE,

    'safe_zone_rule' => <<<'SAFE_RULE_END'
CRITICAL SAFE ZONE: every subject, text glyph, icon, annotation and logo must sit inside the central 70% of the canvas — minimum 15% empty padding from the top, 15% from the bottom, 12% from the left and 12% from the right. The outer ring is pure background. Instagram auto-crops this ring in feed preview; nothing critical can live there.
SAFE_RULE_END,

    'text_rule' => <<<'TEXT_RULE_END'
TEXT: all captions, labels, headlines and dialogue in natural, idiomatic Romanian with correct diacritics (ă, â, î, ș, ț). Avoid translation-like phrasing, redundant reflexives, and regional archaisms. Never English copy, never emoji-only text, never vendor names.
TEXT_RULE_END,

    'do_not_rule' => <<<'DO_NOT_RULE_END'
DO NOT: include any OpenAI/ChatGPT/Gemini/vendor reference, any competitor logo, generic stock-photo cliché, watermark, any language other than Romanian, emoji dumps, low-resolution UI screenshots, or navy-blue ink tones.
DO_NOT_RULE_END,

    // Canonical description of the Sambla brand icon, reused across patterns as {sambla_mark}.
    // The actual mark lives at public/images/logo-icon.svg and may be passed as a reference
    // image to /v1/images/edits for pixel-accurate integration.
    'sambla_mark' => <<<'SAMBLA_MARK_END'
the Sambla brand mark rendered exactly as follows — a rounded-corner squircle (25% corner radius) filled with a smooth red gradient from #991b1b at the top-left to #dc2626 at the bottom-right. Centered horizontally on the squircle, a solid WHITE horizontal pill-shaped visor (rounded-rectangle, about 55% of the squircle width). Inside the white visor, two small solid circles in dark red #991b1b acting as eyes, positioned slightly off-center with a subtle rightward shift. ABSOLUTELY NO antenna, NO mouth, NO smile curve, NO hands, NO feet, NO limbs of any kind, NO extra appendages, NO cheeks, NO speech bubble coming from it. The mark is a clean minimal device-like symbol — it may be tilted playfully, surrounded by a faint coral glow, or have tiny animated sparkles around it to suggest life, but it must NEVER be anthropomorphized into a cartoon character
SAMBLA_MARK_END,

    'patterns' => [

        'bento_editorial_portrait' => [
            'aspect_ratio' => '4:5',
            'category' => 'photo',
            'weight' => 0.25,
            'description' => 'Bento grid: editorial portrait hero + pull quote + headline metric + mini bilateral chat.',
            'required_copy' => ['pull_quote', 'attribution', 'metric_number', 'metric_caption', 'chat_client', 'chat_agent', 'footer_tag'],
            'default_copy' => [
                'pull_quote' => '"Preia apelurile chiar și sâmbăta, când nu avem cum să răspundem."',
                'attribution' => 'DR. ANDREI M. · MEDIC VETERINAR',
                'metric_number' => '+38%',
                'metric_caption' => 'programări confirmate · ultimele 30 zile',
                'chat_client' => 'Pot programa o vizită pentru cățelul meu?',
                'chat_agent' => 'Sigur, joi la 11:00 e liber.',
                'footer_tag' => '✦ răspunde 24/7',
            ],
            'template' => <<<'TEMPLATE_END'
LAYOUT: a clean bento-grid composition on Cream #F5F1E8 background, inside the safe zone. 4 rectangular tiles with 16px gaps and 48px corner radius, each casting the brand-standard soft shadow.

TILE A (spans full safe-zone width, top half): editorial portrait photograph of {subject_hint}. Soft window light, shallow depth of field, Kodak Portra 400 color science, genuine eye contact with the camera, a subtle film grain overlay.

TILE B (bottom-left, square): solid Coral Red #DC2626 card with a large Romanian pull-quote in Instrument Sans medium weight 500, white text: '{pull_quote}' The opening and closing quotation marks are oversized and in a lighter coral tint. Below: thin white hairline and small JetBrains Mono attribution in uppercase wide-tracking: '{attribution}'.

TILE C (bottom-middle, square): solid Ink #1C1917 card with a single massive number in Instrument Sans medium weight 500, warm white: '{metric_number}'. Below: short Inter caption in Muted #78716C: '{metric_caption}'. Top-left corner of this tile: a small emerald green #10B981 ping-pulse dot.

TILE D (bottom-right, square): Paper #FAF7EF card with a mini chat-UI excerpt, two bubbles in strict bilateral order — RIGHT (client, Coral bubble #DC2626, white text): '{chat_client}' then LEFT (agent, Sand bubble #EFE5D0, Ink text): '{chat_agent}'. Bubble radius 18px. Below the chat, a small JetBrains Mono label: '{footer_tag}'.
TEMPLATE_END,
        ],

        'person_annotated_call' => [
            'aspect_ratio' => '4:5',
            'category' => 'photo',
            'weight' => 0.2,
            'description' => 'Editorial photo of someone on a call + floating Sambla-brand annotation cards linked by dotted lines.',
            'required_copy' => ['annot_speed', 'annot_action', 'annot_rating'],
            'default_copy' => [
                'annot_speed' => 'Răspuns în 2 secunde',
                'annot_action' => 'Programare confirmată · marți 14:00',
                'annot_rating' => 'rating 4,9 · 312 conversații',
            ],
            'template' => <<<'TEMPLATE_END'
SUBJECT: editorial photograph of {subject_hint}, holding a phone near the ear, genuine warm smile. Morning light with warm amber undertones, Kodak Portra 400 color science, shallow depth of field, visible texture on clothing, subtle film grain. Background with soft bokeh in Cream and Sand tones so annotation cards read cleanly on top.

OVERLAY GRAPHICS (rendered flat and crisp on top of the photo, inside the safe zone): three floating Sambla-brand annotation cards, each with 24px corner radius, Paper #FAF7EF background, Ink #1C1917 text, soft shadow, connected to the phone by a thin dotted line in Ink —
 1. Top-right of the phone: small card with a rounded waveform icon in Coral on the left and Romanian label on the right: '{annot_speed}'. A tiny emerald ping dot at the top-right corner of this card.
 2. Middle-right: Coral Red #DC2626 pill-shaped card (exception: this one is solid coral with white text) with a white checkmark and text '{annot_action}'.
 3. Bottom-right: small card with a row of 5 coral stars on the left and Romanian text on the right: '{annot_rating}'.

STYLE: Stripe Sessions marketing photography × Figma product annotations — confident, premium, friendly, Romanian-local. Card design must read as native Sambla website UI, not generic overlays.
TEMPLATE_END,
        ],

        'split_before_after' => [
            'aspect_ratio' => '4:5',
            'category' => 'photo',
            'weight' => 0.2,
            'description' => 'Vertical split-screen narrative: left panel problem, right panel solution.',
            'required_copy' => ['left_label', 'right_label', 'left_pain_a', 'left_pain_b', 'right_win_a', 'right_win_b', 'right_chat_bubble'],
            'default_copy' => [
                'left_label' => 'ÎNAINTE',
                'right_label' => 'DUPĂ',
                'left_pain_a' => 'apeluri ratate',
                'left_pain_b' => 'programări uitate',
                'right_win_a' => 'zero apeluri ratate',
                'right_win_b' => 'programări automate',
                'right_chat_bubble' => 'Ți-am confirmat cele 3 programări de mâine.',
            ],
            'template' => <<<'TEMPLATE_END'
LAYOUT: vertical split-screen, 50/50 left-right, separated by a 2px Coral Red #DC2626 vertical rule. Both panels stay fully inside the safe zone. Both share the same Ink #1C1917 typography style.

LEFT PANEL — '{left_label}' (muted, slightly desaturated, cool-leaning): editorial photograph of {subject_hint_before} looking overwhelmed at a messy desk — paper sticky-notes pinned everywhere, an old corded telephone off the hook, a cluttered notebook, cold morning light through a window, slate-gray tint. Small rounded Ink-color label in the top-left corner, JetBrains Mono uppercase wide-tracked: '{left_label}'. Two small Paper-colored pill annotations floating near the clutter, each with a coral ✗ icon and Ink text: '{left_pain_a}' and '{left_pain_b}'.

RIGHT PANEL — '{right_label}' (warm, saturated, cream-bright): {subject_hint_after}, now relaxed, holding their phone up toward the viewer with the SCREEN CLEARLY FACING THE CAMERA — the phone is angled so we see the full front display, never the back. The phone screen shows a clean Romanian messaging app demonstrating that the AI agent is handling incoming calls: one prominent Sand bubble #EFE5D0 with Ink text from the agent reading '{right_chat_bubble}', and a subtle small-caps label at the top of the chat 'Apeluri preluate automat'. The other hand holds a cup of coffee or rests calmly. Warm Portra-400 sun light, terracotta and Sand tones on walls. Small Coral rounded label top-right in JetBrains Mono uppercase: '{right_label}'. Two small Paper pill annotations with emerald green ✓ checkmark and Ink text: '{right_win_a}' and '{right_win_b}'.

STYLE: Apple 'Shot on iPhone' campaign storytelling × editorial documentary photography. Genuine and cinematic, not staged-corporate.
TEMPLATE_END,
        ],

        'flat_illustration_icons' => [
            'aspect_ratio' => '4:5',
            'category' => 'illustration',
            'weight' => 1.0,
            'description' => 'Friendly flat-vector illustration with a character + the Sambla coral agent mascot + four annotated feature cards.',
            'required_copy' => ['card_a', 'card_b', 'card_c', 'card_d'],
            'default_copy' => [
                'card_a' => 'Răspunde în 2 secunde',
                'card_b' => 'Vorbește fluent limba română',
                'card_c' => 'Programări automate 24/7',
                'card_d' => 'Zero apeluri ratate',
            ],
            'template' => <<<'TEMPLATE_END'
STYLE: modern flat vector illustration in the spirit of Figma Config / Webflow / Notion illustration library, tuned to the Sambla warm palette — NOT a cool-blue SaaS illustration. Soft Cream #F5F1E8 background with a faint radial peach #F2E59A glow in the upper-left quadrant at 10% opacity. No photography.

SCENE: {subject_hint} (illustrated, slightly stylized, warm features, relaxed posture) at a desk with a laptop, smiling while holding a phone near the ear. On the desk: a small plant in a terracotta pot, a cream coffee mug, an open notebook. Floating warmly next to the subject, like a helpful companion: {sambla_mark}.

ANNOTATIONS (four floating rounded cards, 24px radius, Paper #FAF7EF background, Ink text, Coral icons, subtle shadow, connected to the scene by thin dotted Ink lines) —
 • Top-left card: small clock icon + '{card_a}'.
 • Top-right card: small speech-bubble icon + '{card_b}'.
 • Bottom-left card: small calendar icon + '{card_c}'.
 • Bottom-right card: small chart icon + '{card_d}'.
All icons are stroke-style, 2px weight, Ink #1C1917.

MOOD: friendly, human, premium-tech, slightly playful but confident — the aesthetic of the Sambla hero section on the live site, not a generic startup illustration.
TEMPLATE_END,
        ],

        'testimonial_pullquote' => [
            'aspect_ratio' => '4:5',
            'category' => 'photo',
            'weight' => 0.2,
            'description' => 'Editorial portrait photograph + oversized pull-quote with coral quotation marks + monospace attribution.',
            'required_copy' => ['pull_quote', 'attribution'],
            'default_copy' => [
                'pull_quote' => '"Îmi preia comenzile chiar și când frământ aluatul."',
                'attribution' => 'MARIA T. · COFETĂRIE BUCUREȘTI',
            ],
            'template' => <<<'TEMPLATE_END'
LAYOUT: editorial magazine layout with a large portrait photograph in the top 60% of the safe zone, and a pull-quote typographic block in the bottom 40%.

TOP — PORTRAIT: warm close-up editorial portrait of {subject_hint}, standing at the entrance of their workplace during golden hour. Shallow depth of field, Kodak Portra 400 color science, warm Cream and terracotta tones, visible film grain. Genuine smile, soft direct eye contact.

BOTTOM — PULL QUOTE on Cream #F5F1E8: large Romanian quote set in Instrument Sans medium weight 500 (never bold), Ink #1C1917, two lines centered: '{pull_quote}' The opening and closing quotation marks are oversized and set in Coral Red #DC2626. Below the quote: a thin Line #E7E0CE hairline, then a small JetBrains Mono attribution in uppercase wide-tracking, Muted #78716C: '{attribution}'.

STYLE: New York Times Magazine portrait feature × It's Nice That editorial typography, on the Sambla warm palette. Human, premium, confident.
TEMPLATE_END,
            'category' => 'photo',
            'weight' => 0.25,
        ],

        'bento_illustrated_stats' => [
            'aspect_ratio' => '4:5',
            'category' => 'illustration',
            'weight' => 1.0,
            'description' => 'Bento grid but with a flat-illustrated niche scene as the hero tile (no photography), plus quote + metric + chat tiles.',
            'required_copy' => ['pull_quote', 'attribution', 'metric_number', 'metric_caption', 'chat_client', 'chat_agent', 'footer_tag'],
            'default_copy' => [
                'pull_quote' => '"Preia apelurile chiar și sâmbăta, când nu avem cum să răspundem."',
                'attribution' => 'DR. ANDREI M. · MEDIC VETERINAR',
                'metric_number' => '+38%',
                'metric_caption' => 'programări confirmate · ultimele 30 zile',
                'chat_client' => 'Pot programa o vizită pentru cățelul meu?',
                'chat_agent' => 'Sigur, joi la 11:00 e liber.',
                'footer_tag' => '✦ răspunde 24/7',
            ],
            'template' => <<<'TEMPLATE_END'
LAYOUT: clean bento-grid composition on Cream #F5F1E8 background, inside the safe zone. 4 rectangular tiles with 16px gaps and 48px corner radius, each casting the brand-standard soft shadow.

TILE A (spans full safe-zone width, top half): warm flat-vector illustration of {subject_hint} in their niche workspace. The illustration must NOT be photographic — use a friendly flat style (Figma Config / Notion / Webflow illustration library quality) tuned to the Sambla warm palette (Cream, Paper, Coral, gentle Ink strokes, warm amber glow in the background). Scatter {niche_graphics} naturally around the character (on the desk, on the walls, in hands) as niche-defining props. In one corner of the tile, floating at a subtle tilt: {sambla_mark}. Motion sparkles and tiny dotted trails suggest a kinetic, animated feel.

TILE B (bottom-left, square): solid Coral Red #DC2626 card with a large Romanian pull-quote in Instrument Sans medium weight 500, white text: '{pull_quote}' The opening and closing quotation marks are oversized, lighter coral tint. Below: thin white hairline and small JetBrains Mono attribution in uppercase wide-tracking: '{attribution}'.

TILE C (bottom-middle, square): solid Ink #1C1917 card with a single massive number in Instrument Sans medium weight 500, warm white: '{metric_number}'. Below: short Inter caption in Muted #78716C: '{metric_caption}'. Top-left corner of this tile: a small emerald green #10B981 ping-pulse dot.

TILE D (bottom-right, square): Paper #FAF7EF card with a mini chat-UI excerpt, two bubbles in strict bilateral order — RIGHT (client, Coral bubble #DC2626, white text): '{chat_client}' then LEFT (agent, Sand bubble #EFE5D0, Ink text): '{chat_agent}'. Bubble radius 18px. Below the chat, a small JetBrains Mono label: '{footer_tag}'.
TEMPLATE_END,
        ],

        'icon_grid_features' => [
            'aspect_ratio' => '4:5',
            'category' => 'illustration',
            'weight' => 1.0,
            'description' => 'Four feature cards in a 2x2 grid with stroke-style illustrated icons. No characters, no photography. Niche graphics appear as accent motifs in the background.',
            'required_copy' => ['headline', 'feat_1_title', 'feat_1_desc', 'feat_2_title', 'feat_2_desc', 'feat_3_title', 'feat_3_desc', 'feat_4_title', 'feat_4_desc', 'footer_tag'],
            'default_copy' => [
                'headline' => 'Un agent AI care nu obosește.',
                'feat_1_title' => 'Răspunde în 2 secunde',
                'feat_1_desc' => 'Fără timpi morți, fără apeluri pierdute.',
                'feat_2_title' => 'Vorbește fluent limba română',
                'feat_2_desc' => 'Accent natural, diacritice corecte.',
                'feat_3_title' => 'Programări automate',
                'feat_3_desc' => 'Sincronizate direct în calendarul tău.',
                'feat_4_title' => 'Funcționează 24/7',
                'feat_4_desc' => 'Weekend, sărbători, nopți — e acolo.',
                'footer_tag' => '✦ Agenți AI pentru {niche_label}',
            ],
            'template' => <<<'TEMPLATE_END'
LAYOUT: infographic-style composition on Cream #F5F1E8 background with a faint radial peach #F2E59A glow in the upper center at 8% opacity. NO characters, NO photography. At the top: a medium-weight Romanian headline in Instrument Sans 500, Ink #1C1917, centered, 2 lines max: '{headline}'. Below the headline: a 2x2 grid of 4 feature cards, 24px radius, Paper #FAF7EF background, subtle soft shadow, 16px gaps.

Each card contains (stacked vertically, 24px inner padding):
 - A stroke-style illustrated icon at the top, 2px weight, Ink #1C1917 with a single Coral #DC2626 accent stroke. Icons follow a consistent flat line-art style (Feather / Lucide).
 - A small bold Inter 600 title in Ink: '{feat_X_title}'.
 - A one-line Inter 400 description in Muted #78716C: '{feat_X_desc}'.

CARDS (clockwise from top-left):
 • Card 1 — clock icon — '{feat_1_title}' / '{feat_1_desc}'
 • Card 2 — speech-bubble icon — '{feat_2_title}' / '{feat_2_desc}'
 • Card 3 — chart icon — '{feat_3_title}' / '{feat_3_desc}'
 • Card 4 — calendar icon — '{feat_4_title}' / '{feat_4_desc}'

BACKGROUND MOTIFS (behind and between cards, VERY low opacity 6-10%, Ink strokes): scatter {niche_graphics} as subtle decorative silhouettes so the viewer senses the niche at a glance.

FOOTER: at the very bottom of the safe zone, a small JetBrains Mono uppercase wide-tracked line in Muted: '{footer_tag}'.

STYLE: Linear × Notion × Apple design-system aesthetic. Confident, clean, premium, modern infographic.
TEMPLATE_END,
        ],

        'niche_vignette_scene' => [
            'aspect_ratio' => '4:5',
            'category' => 'illustration',
            'weight' => 1.0,
            'description' => 'A full flat-illustrated niche scene (workspace of the business) with the coral agent mascot integrated as a helper and 1-2 annotation pills with key message.',
            'required_copy' => ['headline', 'annot_a', 'annot_b', 'footer_tag'],
            'default_copy' => [
                'headline' => 'Agentul tău AI, integrat direct în business.',
                'annot_a' => 'preia apelurile',
                'annot_b' => 'programări automate',
                'footer_tag' => '✦ Agenți AI pentru {niche_label}',
            ],
            'template' => <<<'TEMPLATE_END'
STYLE: full-bleed warm flat-vector illustration (no photography), in the spirit of Figma Config × Notion × Webflow, tuned to the Sambla palette. Friendly, kinetic, slightly playful but premium. Subtle film-grain texture.

SCENE (fills the central safe zone): a full illustrated interior of a {niche_scene} — everything rendered as warm flat vector with gentle gradients. The space is alive with {niche_graphics} placed naturally where they belong (on counters, walls, shelves, tables). Warm Cream #F5F1E8 walls, Sand #EFE5D0 floor, small peach-glow light from a window, a potted plant in terracotta.

In the middle of the scene, floating at a subtle playful tilt: {sambla_mark}. Thin dotted coral lines connect the mark to 2-3 elements in the scene (the phone, the calendar, the counter), suggesting it 'helps' the business.

TOP HEADLINE: inside the safe zone, just below the ceiling of the illustration, a medium-weight Instrument Sans 500 headline in Ink, max 2 lines, centered: '{headline}'.

ANNOTATION PILLS (flat, floating inside the scene, 24px radius, Paper background, Ink text): two coral-accented pills, each with a small stroke icon (phone / calendar / check) and one short Romanian label — '{annot_a}' and '{annot_b}'.

FOOTER: at the bottom of the safe zone, small JetBrains Mono uppercase wide-tracked Muted text: '{footer_tag}'.
TEMPLATE_END,
        ],

        'vertical_timeline_flow' => [
            'aspect_ratio' => '4:5',
            'category' => 'illustration',
            'weight' => 1.0,
            'description' => '3-step vertical flow with coral connectors. NOT a grid — each block is offset left/right for visual rhythm.',
            'required_copy' => ['headline', 'step_1_title', 'step_1_desc', 'step_2_title', 'step_2_desc', 'step_3_title', 'step_3_desc'],
            'default_copy' => [
                'headline' => 'Cum preia agentul AI un apel?',
                'step_1_title' => 'Clientul sună',
                'step_1_desc' => 'Telefonul e preluat la primul apel, fără așteptare.',
                'step_2_title' => 'Agentul răspunde în română',
                'step_2_desc' => 'Ascultă, înțelege și oferă răspunsul potrivit în secunde.',
                'step_3_title' => 'Primești rezumatul',
                'step_3_desc' => 'Vezi pe telefon ce s-a discutat și ce trebuie să faci.',
            ],
            'template' => <<<'TEMPLATE_END'
LAYOUT: vertical timeline flow on a Cream #F5F1E8 background. NOT a grid — three stacked blocks connected by thin coral dotted vertical lines with small coral arrow tips. Each block is intentionally offset horizontally (block 1 slightly left, block 2 center-right, block 3 slightly left again) to create visual rhythm and avoid feeling like a table.

TOP HEADLINE (centered, in top ~12% of safe zone): Instrument Sans medium weight 500, Ink #1C1917, 1 line, tight letter-spacing: '{headline}'.

BLOCK 1 (left-aligned, below headline, ~25% of safe-zone height):
 - Round coral badge #DC2626 on the left with a white "01" in Instrument Sans 500.
 - Paper #FAF7EF rounded card (24px radius) to the right of the badge, with a subtle shadow. Inside: small Ink title '{step_1_title}' in Inter 600, and below it Muted #78716C description '{step_1_desc}' in Inter 400. A small Coral stroke icon of a phone at the top-right corner of the card.

CONNECTOR 1: vertical dotted Ink line from the bottom of block 1 to the top of block 2, ~15% of canvas height. A small coral ▼ arrow mid-line.

BLOCK 2 (right-aligned, offset opposite side):
 - Round coral "02" badge on the RIGHT, card on the LEFT with Ink '{step_2_title}' and Muted '{step_2_desc}'. A small Coral stroke icon of a speech bubble in the card corner.

CONNECTOR 2: dotted line with coral arrow.

BLOCK 3 (left-aligned again):
 - Round coral "03" badge, Paper card with '{step_3_title}' and '{step_3_desc}'. A small Coral stroke icon of a bell / check.
 - Floating softly at the right of block 3, at ~5% canvas width: {sambla_mark}.

BACKGROUND: subtle niche-graphic silhouettes at 6% opacity scattered far in the background ({niche_graphics}).

STYLE: modern product onboarding storyboard — Linear × Notion × Superhuman. Kinetic, confident, human.
TEMPLATE_END,
        ],

        'phone_mockup_focus' => [
            'aspect_ratio' => '4:5',
            'category' => 'illustration',
            'weight' => 0.9,
            'description' => 'Single phone as product hero, screen shows real bilateral chat. NO overlay grid. Floating props + small mascot.',
            'required_copy' => ['headline', 'chat_client', 'chat_agent', 'footer_tag'],
            'default_copy' => [
                'headline' => 'Clienții scriu. Agentul răspunde.',
                'chat_client' => 'Bună, aveți loc sâmbătă dimineața?',
                'chat_agent' => 'Da, vă pot propune 10:00 sau 11:30. Care vă convine?',
                'footer_tag' => '✦ răspuns în sub 3 secunde',
            ],
            'template' => <<<'TEMPLATE_END'
LAYOUT: product-photography-style rendering of ONE single modern smartphone, centered in the safe zone, tilted about 10 degrees to the right, occupying roughly 55% of the canvas height. NO grid, NO overlay cards — just the phone and ambient elements.

BACKGROUND: warm Cream #F5F1E8 with a soft radial peach #F2E59A glow in the upper-right at 12% opacity. Subtle Sand #EFE5D0 gradient at the bottom suggesting a surface. A faint soft shadow directly beneath the phone (suggests it floats).

PHONE SCREEN (crisp, legible): a clean messaging-app UI in the Sambla palette —
 - TOP: a minimal nav bar with "Sambla" in small Inter 600 and a coral ping-pulse dot next to a "Online" label.
 - CHAT AREA (bilateral, strict): RIGHT bubble (client, Coral #DC2626, white text, 18px radius): '{chat_client}'. Then LEFT bubble (agent, Sand #EFE5D0, Ink text, 18px radius): '{chat_agent}'. Comfortable 12px vertical spacing.
 - BOTTOM: an input field with the placeholder "Scrie un mesaj…" in Muted.

FLOATING NICHE PROPS: 3-4 small coral-outlined stroke icons of {niche_graphics}, scattered around the phone at ~12% opacity, suggesting context without crowding.

TOP OF SAFE ZONE (above the phone): one centered Instrument Sans medium-weight headline in Ink, max 2 lines: '{headline}'.

BOTTOM OF SAFE ZONE (below the phone): small JetBrains Mono uppercase wide-tracked line in Muted: '{footer_tag}'.

MASCOT: a small {sambla_mark} floating at the top-right corner of the phone, just outside the device edge, at ~7% canvas width — like a friendly presence, not an overlay.

STYLE: Apple product photography × Aesop skincare minimalism × modern SaaS marketing. Clean, airy, premium.
TEMPLATE_END,
        ],

        'poster_typography_hero' => [
            'aspect_ratio' => '4:5',
            'category' => 'illustration',
            'weight' => 0.9,
            'description' => 'Magazine-cover typography. Headline dominates, one word in coral. No cards, no chat, no grid.',
            'required_copy' => ['kicker', 'headline', 'accent_word', 'support'],
            'default_copy' => [
                'kicker' => 'AGENȚI AI PENTRU AFACERI MICI',
                'headline' => 'Agentul tău nu uită nimic. Niciodată.',
                'accent_word' => 'Niciodată',
                'support' => 'Preia apeluri, programări și mesaje 24/7, în română.',
            ],
            'template' => <<<'TEMPLATE_END'
LAYOUT: magazine-cover / editorial poster energy on Cream #F5F1E8 with a very subtle film-grain texture. TYPOGRAPHY IS THE HERO — NO cards, NO chat bubbles, NO illustration scenes, NO photography, NO icon grids.

TOP STRIP (~8% of safe zone): a thin Coral #DC2626 horizontal rule, and immediately below it a small JetBrains Mono UPPERCASE wide-tracked kicker line in Muted #78716C: '{kicker}'.

HERO HEADLINE (middle 70% of safe zone): MASSIVE Romanian typography in Instrument Sans medium weight 500 (NEVER bold), Ink #1C1917, tight letter-spacing -0.02em, 3 to 4 tight lines, fills the middle of the canvas: '{headline}'.

ACCENT: inside the headline, find the word exactly equal to '{accent_word}' and render it in Coral #DC2626 instead of Ink. Right after that accent word, add a small coral underline stroke or a small coral dot — exactly one subtle emphasis, nothing more.

BOTTOM STRIP (~15% of safe zone): another thin Coral horizontal rule, and a short Inter 400 supporting line centered in Ink: '{support}'. To the right of the support line, at about 5% canvas width, a small {sambla_mark} tilted playfully.

STYLE: Pentagram poster × Apple keynote slide × It's Nice That editorial cover. Confident, quiet, premium. Nothing else on the canvas besides what is described above.
TEMPLATE_END,
        ],

        'data_infographic_chart' => [
            'aspect_ratio' => '4:5',
            'category' => 'illustration',
            'weight' => 1.0,
            'description' => 'Stat-driven poster with a stylized chart (bar / line / donut) as the HERO, annotated with data-point callouts.',
            'required_copy' => ['headline', 'metric_primary', 'metric_primary_caption', 'annot_1', 'annot_2', 'footer_tag'],
            'default_copy' => [
                'headline' => 'O lună cu agentul AI.',
                'metric_primary' => '+38%',
                'metric_primary_caption' => 'programări confirmate vs. luna trecută',
                'annot_1' => 'weekendul nu mai e mort',
                'annot_2' => 'zero apeluri pierdute',
                'footer_tag' => '✦ date din ultimele 30 zile',
            ],
            'template' => <<<'TEMPLATE_END'
LAYOUT: data-viz poster on Cream #F5F1E8 background. NO cards, NO chat, NO character. The HERO is a stylized vector chart filling about 60% of the safe zone.

TOP HEADLINE (~10% of safe zone, centered): Instrument Sans medium 500, Ink #1C1917, max 2 lines: '{headline}'.

HERO CHART (middle 55% of safe zone, centered): a stylized VECTOR bar-chart with 5-7 bars rising from left (smaller) to right (taller), each bar a solid {niche_accent} rectangle with a 4px radius top. The TALLEST bar on the right is thicker and has a small Ink label above it showing the metric '{metric_primary}' in Instrument Sans 500, while the shorter bars sit on a subtle Sand baseline. A soft {niche_accent} glow underneath the tallest bar. Below the chart: a thin horizontal Line in Muted, and centered Inter 400 caption '{metric_primary_caption}'.

ANNOTATIONS (two small Paper pill cards, 24px radius, floating just outside the chart, connected by thin dotted Ink lines to the two rightmost bars):
 - Top-right of the chart: pill with a small coral checkmark icon + '{annot_1}'.
 - Bottom-right of the chart: pill with a small coral ✦ + '{annot_2}'.

BACKGROUND MOTIFS: subtle silhouettes of {niche_graphics} at 5% opacity scattered FAR from the chart so they do not compete with the data.

FOOTER (bottom of safe zone): small JetBrains Mono uppercase wide-tracked in Muted: '{footer_tag}'.
Tiny {sambla_mark} anchored at the bottom-right of the safe zone, ~5% canvas width, tilted slightly.

STYLE: The Economist × Bloomberg × Pitch deck — confident, data-forward, premium.
TEMPLATE_END,
        ],

        'calendar_week_view' => [
            'aspect_ratio' => '4:5',
            'category' => 'illustration',
            'weight' => 0.9,
            'description' => 'A stylized week-calendar mockup as the hero — filled appointments + a few coral highlights showing automated bookings.',
            'required_copy' => ['headline', 'support', 'footer_tag'],
            'default_copy' => [
                'headline' => 'O săptămână bine umplută.',
                'support' => 'Agentul AI preia programările în timp ce tu te ocupi de clienții din față.',
                'footer_tag' => '✦ AGENȚI AI PENTRU {niche_label}',
            ],
            'template' => <<<'TEMPLATE_END'
LAYOUT: Cream #F5F1E8 background. NO grid of cards, NO chat, NO character — the HERO is a flat-vector stylized week-calendar view.

TOP HEADLINE (~10% of safe zone, centered): Instrument Sans medium 500, Ink, 2 lines max: '{headline}'.

HERO CALENDAR (middle 60% of safe zone, centered): a stylized week-view calendar mockup. 7 thin vertical columns, each labeled at the top with the first letter of a Romanian weekday (L, Ma, Mi, J, V, S, D) in JetBrains Mono Muted. Each column has 3-6 thin horizontal "time slot" blocks filled in. Most blocks are Sand #EFE5D0 with a tiny Ink label (a short time like "10:30", "14:00"). About 5-7 of the blocks are {niche_accent} with a small white ✓ icon — these represent automated bookings in the niche accent color. The current day column has a subtle {niche_accent} underline. A small "SĂPT. CURENTĂ" label in JetBrains Mono Muted at the top-left of the calendar.

Floating softly above the calendar, a small {sambla_mark} at ~6% canvas width, subtle coral glow (brand), like it's "helping fill" the calendar. One thin dotted coral line from the mark to a {niche_accent} block, suggesting it booked that slot.

SUPPORT LINE (below calendar): centered Inter 400 in Muted #78716C, 1 line: '{support}'.

NICHE PROPS: 2-3 very small coral stroke icons of {niche_graphics} float at the edges of the safe zone at low opacity.

FOOTER: small JetBrains Mono uppercase wide-tracked at the very bottom: '{footer_tag}'.

STYLE: Notion × Linear × Cron — clean, modern, product-marketing. Not photographic.
TEMPLATE_END,
        ],

        'isometric_workspace_scene' => [
            'aspect_ratio' => '4:5',
            'category' => 'illustration',
            'weight' => 0.9,
            'description' => 'Isometric 3D-feel illustration of a workspace — distinct from the flat-vector family. Slack/Shopify/Stripe marketing vibe.',
            'required_copy' => ['headline', 'support', 'footer_tag'],
            'default_copy' => [
                'headline' => 'Orice business are acum un coleg care nu doarme.',
                'support' => 'Preia apeluri, răspunde în chat, confirmă programări — în română, non-stop.',
                'footer_tag' => '✦ AGENȚI AI PENTRU {niche_label}',
            ],
            'template' => <<<'TEMPLATE_END'
STYLE: isometric vector illustration (NOT flat-on 2D, NOT photography) with gentle gradients and soft shadows, tuned to the Sambla warm palette — Cream, Paper, Coral, Ink, Sand. In the spirit of Slack / Shopify / Stripe landing-page illustrations: depth via isometric projection, friendly geometric shapes, small characters as silhouettes only.

SCENE (fills the central safe zone): an isometric view of a small Romanian business workspace ({niche_scene}). Furniture shown at ~30-degree isometric angle. Key elements: a desk with a laptop (screen toward viewer showing a minimal chat UI with one visible Coral bubble), a chair, a small plant, a wall with shelves, and {niche_graphics} scattered around the scene in their natural locations. Subtle long shadows.

Floating above the desk at a playful tilt: {sambla_mark} — connected to the laptop by a thin coral dotted line suggesting "AI assistant".

Top of safe zone: headline '{headline}' in Instrument Sans medium 500 Ink, 2 lines max, centered.

Below the scene: Inter 400 Muted support line, 1 line, centered: '{support}'.

Bottom of safe zone: JetBrains Mono uppercase wide-tracked footer: '{footer_tag}'.

NO cards grid, NO chat bubbles overlaid, NO annotation pills. The scene speaks for itself.
TEMPLATE_END,
        ],

        'checklist_hero' => [
            'aspect_ratio' => '4:5',
            'category' => 'illustration',
            'weight' => 1.0,
            'description' => 'A large vertical checklist as the HERO design, items crossed off in coral. No character, no chat.',
            'required_copy' => ['headline', 'item_1', 'item_2', 'item_3', 'item_4', 'item_5', 'footer_tag'],
            'default_copy' => [
                'headline' => 'Ce face agentul în locul tău.',
                'item_1' => 'Preia apelul la primul ton',
                'item_2' => 'Răspunde în română, natural',
                'item_3' => 'Confirmă programarea în calendar',
                'item_4' => 'Trimite clientului SMS de confirmare',
                'item_5' => 'Îți lasă ție doar ce e important',
                'footer_tag' => '✦ tot ce nu mai trebuie să faci',
            ],
            'template' => <<<'TEMPLATE_END'
LAYOUT: Cream #F5F1E8 background with a very faint film-grain texture. The HERO is a single large checklist — flat vector, NO cards grid, NO chat, NO characters, NO photography.

TOP HEADLINE (~12% of safe zone, centered): Instrument Sans medium 500 Ink, 2 lines: '{headline}'.

CHECKLIST (middle 70% of safe zone, centered column, max width ~72% of canvas): 5 rows stacked with 24px vertical spacing. Each row has:
 - LEFT: a rounded-square checkbox (16px radius) filled with {niche_accent} with a white ✓ inside (all 5 rows look "checked / done").
 - RIGHT: the item text in Instrument Sans 500 Ink #1C1917, about 2rem size, with a thin {niche_accent} strikethrough line running across the whole text.

The 5 items (in order):
 1. '{item_1}'
 2. '{item_2}'
 3. '{item_3}'
 4. '{item_4}'
 5. '{item_5}'

Subtle peach #F2E59A radial glow behind the checklist at 8% opacity to add warmth.

NICHE: scatter 3-4 tiny coral stroke icons of {niche_graphics} at 10% opacity in the margins of the canvas (not behind the list).

Bottom of safe zone: small JetBrains Mono uppercase Muted wide-tracked footer '{footer_tag}', and a small {sambla_mark} at the bottom-right at ~5% canvas width, tilted.

STYLE: Superhuman × Linear × Notion task lists — clean, confident, satisfying.
TEMPLATE_END,
        ],

        'triptych_phone_carousel' => [
            'aspect_ratio' => '4:5',
            'category' => 'illustration',
            'weight' => 0.8,
            'description' => 'Three phones arranged in a horizontal triptych, each showing a different moment in the client → agent → confirmation flow.',
            'required_copy' => ['headline', 'screen_1_label', 'screen_2_label', 'screen_3_label', 'screen_1_bubble', 'screen_2_bubble', 'screen_3_bubble', 'footer_tag'],
            'default_copy' => [
                'headline' => 'Un apel devine o programare în 3 pași.',
                'screen_1_label' => 'Apel primit',
                'screen_2_label' => 'Agent răspunde',
                'screen_3_label' => 'Confirmare trimisă',
                'screen_1_bubble' => 'Bună ziua, aveți liber mâine?',
                'screen_2_bubble' => 'Da, pot propune 10:30 sau 14:00.',
                'screen_3_bubble' => 'Programare confirmată · mâine 14:00 ✓',
                'footer_tag' => '✦ AGENȚI AI PENTRU {niche_label}',
            ],
            'template' => <<<'TEMPLATE_END'
LAYOUT: Cream #F5F1E8 background with a soft peach radial glow in the upper half at 10% opacity. NO cards grid, NO character — the hero is a horizontal TRIPTYCH of three phones.

TOP HEADLINE (~12% of safe zone, centered): Instrument Sans medium 500 Ink, 1-2 lines: '{headline}'.

TRIPTYCH (middle 65% of safe zone): three identical smartphones arranged horizontally with 16px gaps. Each phone occupies about 28% of the canvas width. The center phone is slightly forward (no tilt), the left phone tilts 6° left, the right phone tilts 6° right. Each phone casts a soft shadow under it.

Each phone screen shows a different moment of the same conversation, numbered "01" → "02" → "03":
 - SCREEN 1: a small "01 · '{screen_1_label}'" label at the top in JetBrains Mono Muted. Below, a single incoming call mockup OR a single chat bubble (RIGHT, Coral #DC2626, white text): '{screen_1_bubble}'.
 - SCREEN 2: "02 · '{screen_2_label}'" label. A single agent bubble (LEFT, Sand #EFE5D0, Ink text): '{screen_2_bubble}'.
 - SCREEN 3: "03 · '{screen_3_label}'" label. A full-width Coral confirmation card with a white checkmark and text '{screen_3_bubble}'.

Between the phones: thin dotted Coral connector lines with small coral arrows (screen 1 → screen 2 → screen 3) indicating flow.

A small {sambla_mark} floats at the top-right of the triptych at ~5% canvas width.

NICHE PROPS: a couple of tiny coral stroke icons of {niche_graphics} at 10% opacity in the far margins.

BOTTOM: small JetBrains Mono uppercase Muted footer: '{footer_tag}'.

STYLE: Apple keynote product triptych × Stripe marketing. Confident, clean, premium.
TEMPLATE_END,
        ],

        'comic_three_panel' => [
            'aspect_ratio' => '4:5',
            'category' => 'illustration',
            'weight' => 0.8,
            'description' => 'A 3-panel comic-strip style storyboard, each panel a different moment with a short line of dialog.',
            'required_copy' => ['headline', 'panel_1_scene', 'panel_1_line', 'panel_2_scene', 'panel_2_line', 'panel_3_scene', 'panel_3_line', 'footer_tag'],
            'default_copy' => [
                'headline' => 'Dimineața ta, fără stres.',
                'panel_1_scene' => 'antreprenorul intră grăbit pe ușă, cafea în mână',
                'panel_1_line' => 'Sigur am pierdut 5 apeluri.',
                'panel_2_scene' => 'deschide telefonul, vede un raport coral',
                'panel_2_line' => 'Toate 5 apeluri: preluate. 3 programări: setate.',
                'panel_3_scene' => 'zâmbește relaxat, așezat la birou',
                'panel_3_line' => 'Atunci... ce fac acum dimineața?',
                'footer_tag' => '✦ AGENȚI AI PENTRU {niche_label}',
            ],
            'template' => <<<'TEMPLATE_END'
LAYOUT: Cream #F5F1E8 background. NO grid of cards, NO chat bubbles overlays, NO hero typography — the HERO is a 3-PANEL COMIC STRIP stacked vertically inside the safe zone.

TOP HEADLINE (~10% of safe zone): Instrument Sans medium 500 Ink, 1 line centered: '{headline}'.

COMIC PANELS (middle 75% of safe zone, stacked vertically with 8px thin Ink dividing lines between them): three equal-height panels rendered as friendly flat-vector illustrations with a comic-strip aesthetic (thin Ink outlines, warm flat fills, subtle shading). Each panel has a small JetBrains Mono "01" / "02" / "03" label in the top-left corner.

PANEL 1 (top): illustration of {panel_1_scene}, set in {niche_scene}. At the bottom of the panel, a small Paper speech balloon with Ink text '{panel_1_line}'.

PANEL 2 (middle): illustration of {panel_2_scene}. Speech balloon or thought cloud with '{panel_2_line}'. A small {sambla_mark} visible on the phone / screen in this panel.

PANEL 3 (bottom): illustration of {panel_3_scene}. Speech balloon with '{panel_3_line}'. Warm sunlight suggests a "happy resolution" vibe.

BOTTOM: a thin Coral horizontal rule, then small JetBrains Mono uppercase wide-tracked Muted footer: '{footer_tag}'.

STYLE: editorial comic-strip illustration × modern SaaS marketing (think Oatly / Mailchimp / Figma with a warm cream palette). Character faces kept simple and friendly, outlines thin and clean.
TEMPLATE_END,
        ],

        'mascot_hero_announcement' => [
            'aspect_ratio' => '4:5',
            'category' => 'illustration',
            'weight' => 1.0,
            'description' => 'The coral agent mascot as the hero character + a big Romanian headline + niche-prop confetti scattered around. For announcements, feature launches, CTAs.',
            'required_copy' => ['headline', 'subheadline', 'cta_label', 'footer_tag'],
            'default_copy' => [
                'headline' => 'Un agent AI care răspunde în locul tău.',
                'subheadline' => 'Apeluri, programări, întrebări — preluate în română, 24/7.',
                'cta_label' => 'Află mai mult →',
                'footer_tag' => '✦ Agenți AI pentru {niche_label}',
            ],
            'template' => <<<'TEMPLATE_END'
STYLE: warm flat-vector illustration on a Cream #F5F1E8 background with a soft radial peach #F2E59A glow in the upper half at 10% opacity. No photography, no human characters.

HERO (centered in the upper half of the safe zone, ~35% canvas width): an oversized rendering of {sambla_mark}. Small coral sparkles and dotted motion trails surround it, suggesting kinetic life — but the mark itself remains exactly as described, no limbs added.

NICHE CONFETTI: scattered around the mascot (NOT overlapping it), at ~15% opacity and 20-30px size each, a gentle confetti of {niche_graphics} — 4-6 small stroke-style icons that instantly telegraph the niche ({niche_scene}).

HEADLINE (middle of safe zone, below the hero): Instrument Sans 500 medium weight, Ink #1C1917, 2 lines centered, tight kerning: '{headline}'. Max 8 words total.

SUBHEADLINE (directly below headline): Inter 400, Muted #78716C, 1 line centered: '{subheadline}'.

CTA PILL (below the subheadline, centered): coral pill #DC2626 with white Inter 500 label + a right-facing arrow: '{cta_label}'. 48px radius, generous horizontal padding.

FOOTER TAG (very bottom of safe zone): small JetBrains Mono uppercase wide-tracked in Muted: '{footer_tag}'.
TEMPLATE_END,
        ],

    ],

    'niche_subjects' => [
        // Reused across patterns as {subject_hint}. Defaults are for 'default'.
        'default' => 'a warm-smiling Romanian small-business owner in their 30s, wearing a cream linen shirt, at a sunlit workplace',
        'veterinar' => 'a warm-smiling male veterinarian in his 40s, wearing cream-colored scrubs, kneeling next to a golden retriever in a sunlit clinic',
        'stomatolog' => 'a warm-smiling female dentist in her 30s wearing soft white scrubs, in a light, minimal dental clinic with wooden accents',
        'contabil' => 'a focused female accountant in her 40s at a wooden desk with a laptop, neatly organized documents, calm natural light through a large window',
        'avocat' => 'a confident male lawyer in his 40s in a well-lit Bucharest office, bookshelf in the background, navy shirt and warm leather chair',
        'salon' => 'a warm-smiling female salon owner in her 30s at the reception counter, eucalyptus plant, brass accents, soft daylight',
        'restaurant' => 'a warm-smiling male restaurant owner in his 40s in his bistro kitchen during prep, linen apron, natural warm light',
        'imobiliare' => 'a professional female real-estate agent in her 30s in a staged modern apartment, warm daylight through floor-to-ceiling windows',
        'auto' => 'a friendly male auto-service owner in his 40s in a clean workshop, navy overalls, organized tool wall',
        'cofetar' => 'a warm-smiling female bakery owner in her late 30s in a flour-dusted apron, standing in front of her shop window during golden hour',
        'consultant' => 'a confident female business consultant in her 30s at a minimal co-working desk, notebook and phone in hand, warm window light',
        'psiholog' => 'a calm female psychologist in her 40s in a warm therapy room with an armchair, a low coffee table and a potted plant, gentle natural daylight',
        'optica' => 'a friendly male optician in his 40s in a bright optical shop with display cases of glasses on warm wooden shelves',
        'scoala_limbi' => 'a warm-smiling female language teacher in her 30s in a cozy classroom with books, posters, and a soft-lit window',
        'turism' => 'a friendly female travel agent in her 30s at a bright office desk with a globe, maps and a laptop, warm natural light',
        'pensiune' => 'a warm-smiling male host in his 40s at the entrance of a cozy countryside guesthouse, linen shirt, stone walls, warm evening light',
        'curatenie' => 'a friendly female team-leader in her 30s in a clean, bright living room holding a neat cleaning caddy, soft daylight',
        'notar' => 'a calm female notary in her 40s in a warm wood-paneled office with documents and a desk lamp, soft diffused light',
        'medic' => 'a warm-smiling female doctor in her 40s in a bright minimal family-medicine clinic, white coat, wooden accents',
    ],

    'niche_subjects_before' => [
        // Variants for split_before_after LEFT panel (same person, frustrated state).
        'default' => 'a Romanian small-business owner (male, 40s)',
        'veterinar' => 'a male veterinarian (40s) in scrubs',
        'stomatolog' => 'a female dentist (30s) in scrubs',
        'contabil' => 'a female accountant (40s) at her desk',
        'avocat' => 'a male lawyer (40s) in his office',
        'salon' => 'a female salon owner (30s) at reception',
        'restaurant' => 'a male restaurant owner (40s) during prep',
        'imobiliare' => 'a female real-estate agent (30s)',
        'auto' => 'a male auto-service owner (40s) in his workshop',
        'cofetar' => 'a female bakery owner (late 30s) in her apron',
        'consultant' => 'a female consultant (30s)',
        'psiholog' => 'a female psychologist (40s) in her therapy room',
        'optica' => 'a male optician (40s) in his shop',
        'scoala_limbi' => 'a female language teacher (30s) in her classroom',
        'turism' => 'a female travel agent (30s) at her desk',
        'pensiune' => 'a male guesthouse host (40s) at his property',
        'curatenie' => 'a female cleaning-team-leader (30s) in a client home',
        'notar' => 'a female notary (40s) in her office',
        'medic' => 'a female family doctor (40s) in her clinic',
    ],

    // Small stroke-style props/icons that telegraph the niche at a glance.
    // Rendered as confetti / background motifs / desk objects depending on the pattern.
    'niche_graphic_elements' => [
        'default' => 'a coffee mug, an open notebook, a smartphone, a potted plant, a sparkle',
        'veterinar' => 'paw prints, a stethoscope, a dog bowl, a small leash, a friendly cat silhouette, a heart',
        'stomatolog' => 'a stylized tooth, a toothbrush, mint leaves, a smile arc, a tiny mirror, a sparkle',
        'contabil' => 'a calculator, a small bar-chart, a calendar page, a pen, a folder, a tiny checkmark',
        'avocat' => 'a balanced scale, a quill pen, a leather-bound book, a small gavel, a document with a seal',
        'salon' => 'open scissors, a comb, a hair-dryer silhouette, a small potted fern, a hand mirror, a sparkle',
        'restaurant' => 'a fork and knife, a steaming plate, a wine glass, a croissant, a small herb sprig, a flame',
        'imobiliare' => 'a house silhouette, a key, a measuring tape, a doorway, a small pin-drop marker, a plant',
        'auto' => 'a wrench, a gear, a car silhouette, a tire, an oil drop, a small spanner',
        'cofetar' => 'a croissant, a coffee cup with steam, a rolling pin, a loaf of bread, a sprinkle of flour, a heart',
        'consultant' => 'a briefcase, a line-chart going up, a laptop, a notebook, a coffee, a lightbulb',
        'psiholog' => 'a soft cloud shape, a puzzle piece, a potted plant, a tissue box, a small breathing-circle motif, a heart',
        'optica' => 'a pair of eyeglasses, a lens, an eye-chart, a frame silhouette, a tiny screwdriver, a sparkle',
        'scoala_limbi' => 'an open book, a speech bubble with letters, a globe, a pencil, a small paper airplane, a sparkle',
        'turism' => 'a tiny airplane, a passport, a suitcase, a sun, a palm leaf, a small pin-drop marker',
        'pensiune' => 'a small house silhouette, a pillow, a coffee cup, a breakfast plate, a small suitcase, a fir tree',
        'curatenie' => 'a spray bottle, a tiny bubble, a broom silhouette, a folded towel, a sparkle, a citrus slice',
        'notar' => 'a rolled document, a wax seal, an ink pen, a stamp, a leather-bound book, a small star',
        'medic' => 'a stethoscope, a heart-pulse line, a small cross, a pill silhouette, a clipboard, a sparkle',
    ],

    // A one-line description of the niche workplace used by scene-oriented patterns.
    'niche_scene' => [
        'default' => 'a warm modern small-business workspace with a desk and natural daylight',
        'veterinar' => 'a small, warm veterinary clinic with an exam table, a shelf of supplies, a dog-friendly mat, a small waiting-bench through a doorway',
        'stomatolog' => 'a bright minimal dental clinic with a modern chair, a large lamp, warm wooden accents and a plant',
        'contabil' => 'a calm accounting office with a wooden desk, a laptop, neatly stacked folders, a large window with warm light',
        'avocat' => 'a warm law office with a bookshelf, a leather chair, a large wooden desk, a green banker lamp',
        'salon' => 'a warm modern salon interior with a styling chair, a large mirror, brass accents, a potted eucalyptus plant',
        'restaurant' => 'a cozy bistro kitchen with an open-flame stove, hanging pots, a wooden prep counter and herbs on the wall',
        'imobiliare' => 'a staged modern apartment living room with floor-to-ceiling windows, warm daylight, minimal decor',
        'auto' => 'a clean neighborhood auto workshop with a tool-wall, a lift, a car silhouette in the background',
        'cofetar' => 'a small warm neighborhood bakery with a display case of pastries, a chalkboard menu, a basket of bread, a potted basil',
        'consultant' => 'a minimal co-working desk by a window with a laptop, a notebook, a coffee, a city view softly blurred',
        'psiholog' => 'a warm therapy room with an armchair, a low coffee table with a tissue box, soft neutral walls and a potted plant',
        'optica' => 'a bright modern optical shop with wooden shelves of eyeglass frames, a small fitting mirror, a counter with a lens-tester',
        'scoala_limbi' => 'a cozy language classroom with a bookshelf, an open book, a small whiteboard with language snippets, plants and warm daylight',
        'turism' => 'a warm travel-agency desk with a globe, a stack of brochures, a laptop and a small potted fern',
        'pensiune' => 'a cozy countryside guesthouse entrance with stone walls, wooden beams, a porch with chairs and warm evening light',
        'curatenie' => 'a bright tidy living room with a cleaning caddy, neatly folded towels, citrus on the counter and soft daylight',
        'notar' => 'a wood-paneled notary office with a large desk, stacked documents, a brass lamp and a leather chair',
        'medic' => 'a bright minimal family-medicine clinic with an exam bench, a wall chart, a small plant and wooden accents',
    ],

    // Short Romanian label for the niche, used in footer tags like "Agenți AI pentru {niche_label}".
    // Niche accent color (echoes the per-niche theme from resources/css/new.css + tailwind.config.js).
    // Used in templates for SECONDARY niche-specific highlights — the brand Coral #DC2626 still runs
    // the hero elements (mascot, CTA, brand lines) so Sambla stays recognizable across the feed.
    'niche_accents' => [
        'default' => '#DC2626',     // coral, brand default
        'veterinar' => '#3B82F6',   // medical blue
        'stomatolog' => '#3B82F6',  // medical blue
        'medic' => '#3B82F6',
        'psiholog' => '#3B82F6',
        'optica' => '#3B82F6',
        'salon' => '#F43F5E',       // beauty rose
        'auto' => '#F97316',        // auto orange
        'restaurant' => '#10B981',  // resto emerald
        'cofetar' => '#10B981',
        'pensiune' => '#10B981',
        'imobiliare' => '#F59E0B',  // imobiliare amber
        'avocat' => '#A855F7',      // legal purple
        'notar' => '#A855F7',
        'scoala_limbi' => '#4F46E5',// education indigo
        'turism' => '#06B6D4',      // travel cyan
        'contabil' => '#DC2626',    // keep coral (finance is brand-neutral)
        'consultant' => '#DC2626',
        'curatenie' => '#10B981',   // emerald freshness
    ],

    'niche_labels' => [
        'default' => 'afacerea ta',
        'veterinar' => 'cabinete veterinare',
        'stomatolog' => 'cabinete stomatologice',
        'contabil' => 'firme de contabilitate',
        'avocat' => 'cabinete de avocatură',
        'salon' => 'saloane de înfrumusețare',
        'restaurant' => 'restaurante și bistrouri',
        'imobiliare' => 'agenții imobiliare',
        'auto' => 'ateliere auto',
        'cofetar' => 'cofetării și brutării',
        'consultant' => 'consultanți independenți',
        'psiholog' => 'cabinete de psihologie',
        'optica' => 'magazine de optică medicală',
        'scoala_limbi' => 'școli de limbi și cursuri',
        'turism' => 'agenții de turism',
        'pensiune' => 'pensiuni și hoteluri mici',
        'curatenie' => 'firme de curățenie',
        'notar' => 'birouri notariale',
        'medic' => 'cabinete medicale',
    ],
];
