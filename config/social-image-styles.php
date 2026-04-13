<?php

/**
 * Visual style presets for social media image generation.
 *
 * Rewritten 2026-04-13: aligned with Sambla brand design system.
 *
 * Brand palette: primary red (#991b1b → #dc2626 → #f87171), slate neutrals
 * (#0f172a → #1e293b → #f8fafc), accent amber/emerald for contrast.
 * Design language: rounded-2xl cards, glassmorphism, Inter font,
 * pill-shaped CTAs, Romanian geometric motif accents.
 */
return [
    // ═══════════════════════════════════════
    // DARK STYLES
    // ═══════════════════════════════════════

    'brand_glassmorphism' => [
        'name' => 'Brand Glassmorphism Chat',
        'prompt' => 'Premium glassmorphism UI mockup — a frosted glass card with large rounded corners (16px radius) floating over a gradient background from deep slate (#0f172a) to dark red (#7f1d1d). Thin white border at 20% opacity, soft blur, inner glow. Inside: abstract chat elements — rounded message bubbles in red (#dc2626) and slate (#334155), avatar dots, green status indicator, typing dots. Soft red ambient glow, floating bokeh particles. Dribbble quality.',
    ],

    'dark_dashboard_cards' => [
        'name' => 'Dark Dashboard Cards',
        'prompt' => 'Dark-mode dashboard at slight perspective — floating UI cards with rounded corners (16px) showing abstract data: sentiment gauge with green/amber/red zones, counter card, metric card, chat preview card. Glassmorphism cards with frosted background, thin white border at 10% opacity. Background: near-black slate (#0f172a). Accents: red (#dc2626), green (#059669), amber (#d97706). Glow effects on active cards. NO readable text — abstract colored shapes only.',
    ],

    'gradient_hero_device' => [
        'name' => 'Gradient Hero Device',
        'prompt' => 'Sleek smartphone floating at angle showing abstract blurred chat interface — rounded shapes in red (#f87171) and grey (#e2e8f0) on dark (#0f172a) screen. Phone floats over gradient from slate (#0f172a) to dark red (#991b1b). Red rim light on edges. Glowing particles and bokeh. Apple product page quality.',
    ],

    'dark_chat_conversation' => [
        'name' => 'Dark Chat Conversation',
        'prompt' => 'Dark UI with abstract chat conversation on phone/tablet screen. Dark slate (#0f172a) background. Alternating bubbles: user in slate (#334155), AI in red (#dc2626), rounded corners (12px). Avatar circles. Typing indicator. Screen has soft glow. Background gradient mesh in dark red and slate. Premium messaging aesthetic.',
    ],

    'dark_gradient_abstract' => [
        'name' => 'Dark Gradient Abstract',
        'prompt' => 'Abstract gradient composition — smooth flowing shapes on dark slate (#0f172a) with vibrant red (#dc2626), coral (#f87171), and amber (#d97706) accents. Organic flowing forms suggesting AI intelligence. Subtle grain texture. One bold geometric focal element. Premium album cover / tech wallpaper mood.',
    ],

    'geometric_motif_dark' => [
        'name' => 'Geometric Motif Dark',
        'prompt' => 'Bold geometric composition inspired by Romanian embroidery — diamonds, rhombuses, zigzag lines in modern Bauhaus grid. Red (#991b1b, #dc2626) dominant, deep slate (#1e293b) background, cream (#f8fafc) accents, amber (#d97706) touches. Clean, precise, modern — not folksy. Swiss design meets traditional craft. Museum poster quality.',
    ],

    'dark_notification_stack' => [
        'name' => 'Dark Notification Stack',
        'prompt' => 'Stack of floating notification cards on a dark slate (#0f172a) background — cards cascade from top to bottom with slight offset and rotation. Each card has rounded corners (12px), frosted glass effect. Cards suggest: new message received (red dot), call answered (green dot), appointment confirmed (amber checkmark). Soft glow around top card. Red (#dc2626) accent on primary notification. Subtle depth blur on back cards. iOS notification center aesthetic but darker and more premium.',
    ],

    'dark_voice_waveform' => [
        'name' => 'Dark Voice Waveform',
        'prompt' => 'Abstract voice/audio waveform visualization on dark slate (#0f172a) — a flowing sound wave in warm red (#dc2626) to coral (#f87171) gradient, pulsing with energy. The waveform is smooth and organic, not jagged. Subtle circular ripples emanate from the center suggesting voice. Small microphone icon silhouette integrated into the wave. Ambient red glow. Background has faint grid lines at very low opacity. The mood is: voice AI, natural conversation, alive. Premium music app aesthetic.',
    ],

    'dark_3d_shapes' => [
        'name' => 'Dark 3D Floating Shapes',
        'prompt' => 'Premium 3D scene with floating geometric shapes on dark slate (#0f172a) — a glossy red (#dc2626) sphere, a frosted glass cube, an amber (#d97706) torus, a slate metallic cylinder. Objects float at different heights with soft shadows below. Colored rim lights: red from left, blue-slate from right. Subtle depth of field. Cinema 4D / Blender quality. The shapes suggest building blocks of AI technology. Clean, abstract, premium.',
    ],

    // ═══════════════════════════════════════
    // LIGHT STYLES
    // ═══════════════════════════════════════

    'light_dashboard_cards' => [
        'name' => 'Light Dashboard Cards',
        'prompt' => 'Light-mode dashboard at slight perspective — floating UI cards with rounded corners (16px) and soft shadows on white (#f8fafc) to light grey (#f1f5f9) background. Cards: sentiment gauge, counter with red (#dc2626) accent bar, metric card, chat preview. White card backgrounds, subtle border (#e2e8f0), soft drop shadows. Red for primary metrics, green (#059669) for positive, amber (#d97706) for warnings. Airy whitespace. Apple Health / Linear aesthetic.',
    ],

    'light_chat_ui' => [
        'name' => 'Light Chat Interface',
        'prompt' => 'Clean light-mode chat mockup on bright white (#ffffff) to cream (#f8fafc) background. Rounded message bubbles: user in light grey (#f1f5f9), AI in soft red (#fee2e2) with red (#dc2626) left border. Friendly avatar circles. Pill-shaped input with red send button. Generous whitespace. Subtle decorative dots in red and amber. Intercom / Crisp aesthetic.',
    ],

    'isometric_saas' => [
        'name' => 'Isometric SaaS Workspace',
        'prompt' => 'Clean isometric 3D workspace — laptop with abstract red (#dc2626) dashboard, smartphone with chat bubbles, coffee mug with red stripe, geometric decorations. Cream (#f8fafc) surface, slate (#1e293b) screens. Rounded shapes (16px radius). Soft shadows, ambient occlusion. Stripe/Notion illustration style. Friendly, professional.',
    ],

    'flat_scene_business' => [
        'name' => 'Flat Business Scene',
        'prompt' => 'Flat vector illustration — shop/office counter with phone notification, friendly AI shape (rounded red #dc2626 speech bubble, NOT robot). Business owner as geometric silhouette. Cream (#f8fafc) background, red (#991b1b) AI element, slate (#64748b) neutrals, amber (#d97706) accent. Mailchimp/Intercom style — warm, human.',
    ],

    'product_lifestyle_warm' => [
        'name' => 'Warm Product Lifestyle',
        'prompt' => 'Warm lifestyle photography — smartphone on wooden desk showing abstract chat (blurred, no readable text). Coffee cup, succulent, golden hour window light. Shallow depth of field. Subtle red accents (notebook edge, pen cap). Calm productive morning mood.',
    ],

    'light_gradient_soft' => [
        'name' => 'Light Gradient Soft',
        'prompt' => 'Soft abstract gradient on light background — flowing shapes in pastel red (#fecaca), coral (#f87171 at 30%), cream (#fef2f2) over white (#ffffff). One bold rounded geometric focal element. Subtle grain. Premium SaaS landing page background feel. stripe.com / linear.app aesthetic with warm red tones.',
    ],

    'split_comparison' => [
        'name' => 'Before/After Split',
        'prompt' => 'Split-screen composition — left: chaotic muted grey desk (messy papers, ringing phone, sticky notes, overwhelm), right: clean modern workspace in warm brand colors (clean desk, phone with red notification, coffee, calm light). Clean diagonal split line. Left blurred/faded, right sharp with red (#dc2626) accents. Before AI vs after AI story. Editorial photography quality.',
    ],

    'paper_cutout_playful' => [
        'name' => 'Paper Cutout Playful',
        'prompt' => 'Playful paper cut-out illustration — layered paper shapes with depth. Phone shape, chat bubble papers in red (#dc2626) and cream (#fef2f2), geometric paper stars and circles. Real shadows between layers. Cream base, red (#991b1b), slate (#334155), amber (#d97706). Handcrafted texture. Premium craft illustration for tech brand.',
    ],

    'light_phone_mockup_clean' => [
        'name' => 'Clean Phone Mockup',
        'prompt' => 'Ultra-clean phone mockup on pure white background — modern smartphone centered showing abstract app interface with red (#dc2626) navigation elements, rounded cards, colored dots. Phone has thin bezels, rounded corners. Very subtle shadow beneath phone. Minimal, generous whitespace. One or two small decorative elements floating nearby (a small red circle, a grey rounded rectangle). Apple store product shot aesthetic.',
    ],

    'light_cards_scattered' => [
        'name' => 'Scattered Feature Cards',
        'prompt' => 'Multiple small UI cards scattered playfully on a light cream (#f8fafc) background — each card has rounded corners (16px), soft shadow, and shows one abstract concept: a chat bubble icon, a phone icon, a chart icon, a clock icon, a shield icon. Cards are at slight random angles creating a dynamic layout. Primary card highlighted with red (#dc2626) border. Other cards in white with slate (#334155) icons. Playful but organized. The mood is: so many features, all working together. Bento grid / feature showcase aesthetic.',
    ],

    'geometric_motif_light' => [
        'name' => 'Geometric Motif Light',
        'prompt' => 'Geometric Romanian embroidery pattern on light cream (#fef2f2) background — diamond shapes, rhombuses, crosses in a modern grid. Red (#dc2626) as primary pattern color, lighter red (#fecaca) for secondary shapes, slate (#64748b) for fine detail lines. Clean, precise, printed textile feel. Generous whitespace. The pattern suggests Romanian identity and craftsmanship in a modern context. Art print quality.',
    ],

    'light_illustration_people' => [
        'name' => 'Abstract People Illustration',
        'prompt' => 'Minimalist flat illustration of abstract human figures interacting with technology — simple geometric shapes suggesting a business owner looking at a phone with a friendly red (#dc2626) chat notification. Figures are simplified abstract shapes (circles for heads, rounded rectangles for bodies) in slate tones. Clean white background with one red accent element. Generous whitespace. Google Workspace / Notion illustration style — inclusive, friendly, modern. NOT realistic people — purely geometric/abstract representations.',
    ],
];
