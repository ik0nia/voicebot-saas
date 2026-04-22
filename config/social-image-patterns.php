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

    'patterns' => [

        'bento_editorial_portrait' => [
            'aspect_ratio' => '4:5',
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

SCENE: {subject_hint} (illustrated, slightly stylized, warm features, relaxed posture) at a desk with a laptop, smiling while holding a phone near the ear. On the desk: a small plant in a terracotta pot, a cream coffee mug, an open notebook. The AI agent is represented as a small friendly Coral squircle character #DC2626 with gentle dot eyes and a tiny antenna tipped with a green ping dot — floating warmly next to the subject like a helpful companion. The character must echo the Sambla site's small animated agent glyph.

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
    ],
];
