#!/usr/bin/env bash
# gpt-image-2 premium prompt catalog for Social Media Factory.
# Every prompt locks the Sambla brand design language (site-aligned palette, typography,
# card radius, tone) + safe zones + Romanian text + no vendor reveals.

set -euo pipefail

cd "$(dirname "$0")/.."

KEY=$(grep '^OPENAI_API_KEY=' .env | head -1 | cut -d= -f2- | tr -d '"'"'")

SLUG="${1:-bento_editorial_portrait}"
OUT_DIR="public/test-gpt-image-2"
mkdir -p "$OUT_DIR"

# Universal BRAND PREAMBLE — echoes sambla.ro design system so social posts feel
# continuous with the web surface. Uses hex codes from tailwind.config.js / new.css.
BRAND='BRAND DESIGN LANGUAGE (must match the Sambla website exactly):
- Base palette: Cream #F5F1E8 (primary background), Paper #FAF7EF (secondary), Sand #EFE5D0 (tertiary).
- Text/ink: Ink #1C1917 (warm dark brown — NEVER pure black, NEVER navy blue), Ink Soft #3A3532 for muted text.
- Primary accent: Coral Red #DC2626, with Dark Red #991B1B used only for depth in gradients.
- Secondary glows: soft peach #F2E59A and soft lilac #C7B8E8, only as low-opacity radial gradients in backgrounds.
- Typography: display headings in Instrument Sans, medium weight 500 (never bold), tight letter-spacing around -0.02em. Body in Inter, 400-600. Small caps / labels in JetBrains Mono, wide tracking, muted color.
- Surfaces: cards with large rounded corners (48px radius), soft shadow (like box-shadow 0 20px 40px -10px rgba(0,0,0,0.08)), no hard borders.
- Signature accents: a coral ✦ symbol used as list bullet; a ◇ folk diamond in section headers; an emerald green #10B981 live-ping dot for status indicators; emerald checkmarks.
- Subtle film grain overlay on photographic content, very low intensity.

TONE: warm, Romanian-local, human-first, premium but approachable. NEVER generic SaaS stock, NEVER futuristic neon/cyber, NEVER dark dystopian backgrounds, NEVER corporate clinical.'

SAFE_ZONE='CANVAS: 1024x1536, portrait 4:5, Instagram feed post.
CRITICAL SAFE ZONE: every subject, text glyph, icon, annotation and logo must sit inside the central 70% of the canvas — minimum 15% empty padding from the top, 15% from the bottom, 12% from the left and 12% from the right. The outer ring is pure background. Instagram auto-crops this ring in feed preview; nothing critical can live there.'

TEXT='TEXT: all captions, labels, headlines and dialogue in natural Romanian with correct diacritics (ă, â, î, ș, ț). Never use English copy, emoji-only text, placeholder lorem ipsum, or brand names of vendors.'

DONOT='DO NOT: include any OpenAI/ChatGPT/Gemini/vendor reference, any competitor logo, stock-photo cliché, watermark, any language other than Romanian, generic emoji dumps, or low-resolution UI screenshots.'

case "$SLUG" in

  bento_editorial_portrait)
    SIZE="1024x1536"
    PROMPT="$BRAND

$SAFE_ZONE

LAYOUT: a clean bento-grid composition on Cream #F5F1E8 background, inside the safe zone. 4 rectangular tiles with 16px gaps and 48px corner radius, each tile casts the brand-standard soft shadow.

TILE A (spans full safe-zone width, top half): editorial portrait photograph of a warm-smiling male veterinarian in his 40s, wearing cream-colored scrubs, kneeling next to a golden retriever in a sunlit clinic, soft window light from the left, shallow depth of field, Kodak Portra 400 color science, genuine eye contact with the camera. Photograph has a subtle film grain.

TILE B (bottom-left, square): solid Coral Red #DC2626 card with a large Romanian pull-quote in Instrument Sans medium, weight 500, optical kerning, white text on coral: '\"Agentul nostru preia apelurile chiar și sâmbăta.\"' The opening and closing quotation marks are oversized, in a lighter coral tint. Below, a thin white hairline and a small JetBrains Mono attribution in uppercase: 'DR. ANDREI M. · MEDIC VETERINAR'.

TILE C (bottom-middle, square): solid Ink #1C1917 card with a single massive number set in Instrument Sans medium, warm white: '+38%'. Below it a short Inter caption in Muted #78716C: 'programări confirmate · ultimele 30 zile'. At the top-left corner of this tile, a small emerald green ping-pulse dot #10B981.

TILE D (bottom-right, square): Paper #FAF7EF card showing a mini chat-UI excerpt with two bubbles in strict bilateral order — RIGHT (client, Coral bubble #DC2626, white text): 'Pot programa o vizită pentru cățelul meu?' then LEFT (agent, Sand bubble #EFE5D0, Ink text): 'Sigur, joi la 11:00 e liber.' Bubbles have 18px radius. Below the chat, a small JetBrains Mono label: '✦ răspunde 24/7'.

$TEXT
$DONOT"
    ;;

  person_annotated_call)
    SIZE="1024x1536"
    PROMPT="$BRAND

$SAFE_ZONE

SUBJECT: editorial photograph of a warm-smiling Romanian woman in her early 30s, genuine laugh, sitting in a sunlit modern café in Bucharest, holding her phone near her ear. Morning light with warm amber undertones, Kodak Portra 400 color science, shallow depth of field, visible texture on her linen shirt, a subtle film grain overlay. The café background has a soft bokeh with cream and sand tones so annotation cards read cleanly on top.

OVERLAY GRAPHICS (rendered flat and crisp on top of the photo, inside the safe zone): three floating Sambla-brand annotation cards, each with 24px corner radius, Paper #FAF7EF background, Ink text, soft shadow, connected to the phone by a thin dotted line in Ink #1C1917 —
 1. Top-right of the phone: small card with a rounded waveform icon (coral) on the left and Romanian label on the right: 'Răspuns în 2 secunde'. A tiny emerald ping dot at the top-right corner of this card.
 2. Middle-right: Coral Red #DC2626 pill-shaped card (exception: this one is solid coral with white text) with a white checkmark and text 'Programare confirmată · marți 14:00'.
 3. Bottom-right: small card with a row of 5 coral stars on the left and Romanian text on the right: 'rating 4,9 · 312 conversații'.

STYLE: Stripe Sessions marketing photography × Figma product annotations — confident, premium, friendly, Romanian-local. The card design must feel like native Sambla website UI, not generic overlays.

$TEXT
$DONOT"
    ;;

  split_before_after)
    SIZE="1024x1536"
    PROMPT="$BRAND

$SAFE_ZONE

LAYOUT: vertical split-screen, 50/50 left-right, separated by a 2px Coral Red #DC2626 vertical rule. Both panels stay fully inside the safe zone. Both panels share the same Ink #1C1917 typography style.

LEFT PANEL — 'ÎNAINTE' (muted, slightly desaturated, cool-leaning): editorial photograph of a small Romanian business owner (male, 40s) looking overwhelmed at a messy desk — paper sticky-notes pinned everywhere, an old corded telephone off the hook, a cluttered notebook, cold morning light through a window, slate-gray tint. A small rounded Ink-color label in the top-left corner in JetBrains Mono uppercase wide-tracked: 'ÎNAINTE'. Two small Paper-colored pill annotations floating near the clutter, each with a coral ✗ icon and Ink text: 'apeluri ratate' and 'programări uitate'.

RIGHT PANEL — 'DUPĂ' (warm, saturated, cream-bright): same person, now relaxed, sitting back with a cup of coffee in hand, smiling softly at their phone. The phone screen shows a clean Romanian chat with one visible Coral bubble: 'Ți-am confirmat cele 3 programări de mâine.' Warm Portra-400 sun light, terracotta and Sand tones on walls. A small Coral rounded label top-right in JetBrains Mono uppercase: 'DUPĂ'. Two small Paper pill annotations with emerald green ✓ checkmark and Ink text: 'zero apeluri ratate' and 'programări automate'.

STYLE: Apple 'Shot on iPhone' campaign storytelling × editorial documentary photography. Genuine and cinematic, not staged-corporate.

$TEXT
$DONOT"
    ;;

  flat_illustration_icons)
    SIZE="1024x1536"
    PROMPT="$BRAND

$SAFE_ZONE

STYLE: modern flat vector illustration in the spirit of Figma Config / Webflow / Notion illustration library, but tuned to the Sambla warm palette — NOT a cool-blue SaaS illustration. Soft Cream #F5F1E8 background with a faint radial peach #F2E59A glow in the upper-left quadrant at 10% opacity. No photography.

SCENE: a cheerful Romanian woman (illustrated, slightly stylized, warm brown hair, diverse features, relaxed posture) sitting at a desk with her laptop, smiling while holding her phone near her ear. On her desk: a small plant in a terracotta pot, a cream coffee mug, an open notebook. The AI agent is represented as a small friendly coral squircle character #DC2626 with gentle dot eyes and a tiny antenna with a green ping dot on top — floating warmly next to her like a helpful companion. The character must visually echo the Sambla site's small animated robot glyph.

ANNOTATIONS (four floating rounded cards, 24px radius, Paper #FAF7EF background, Ink text, Coral icons, subtle shadow, connected to the scene by thin dotted Ink lines) —
 • Top-left card: small clock icon + 'Răspunde în 2 secunde'.
 • Top-right card: small speech-bubble icon + 'Vorbește fluent limba română'.
 • Bottom-left card: small calendar icon + 'Programări automate 24/7'.
 • Bottom-right card: small chart icon + 'Zero apeluri ratate'.
All icons are stroke-style, 2px weight, Ink #1C1917.

MOOD: friendly, human, premium-tech, slightly playful but confident — the aesthetic of the Sambla hero section on the live site, not a generic startup illustration.

$TEXT
$DONOT"
    ;;

  testimonial_pullquote)
    SIZE="1024x1536"
    PROMPT="$BRAND

$SAFE_ZONE

LAYOUT: editorial magazine layout with a large portrait photograph in the top 60% of the safe zone, and a pull-quote typographic block in the bottom 40%.

TOP — PORTRAIT: warm, close-up editorial portrait of a Romanian woman in her late 30s, owner of a small Bucharest bakery, wearing a flour-dusted linen apron, standing in front of her shop window during golden hour. Shallow depth of field, Kodak Portra 400, warm Cream and terracotta tones, visible film grain. Genuine smile, soft direct eye contact.

BOTTOM — PULL QUOTE on Cream #F5F1E8: large Romanian quote set in Instrument Sans medium weight 500 (never bold), Ink #1C1917, two lines centered: '\"Îmi preia comenzile chiar și când frământ aluatul.\"' The opening and closing quotation marks are oversized and set in Coral Red #DC2626. Below the quote: a thin Line #E7E0CE hairline, then a small JetBrains Mono attribution in uppercase wide-tracking, Muted #78716C: 'MARIA T. · COFETĂRIE BUCUREȘTI'.

STYLE: New York Times Magazine portrait feature × It's Nice That editorial typography, but on the Sambla warm palette. Human, premium, confident.

$TEXT
$DONOT"
    ;;

  *)
    echo "unknown slug: $SLUG" >&2
    echo "available: bento_editorial_portrait | person_annotated_call | split_before_after | flat_illustration_icons | testimonial_pullquote" >&2
    exit 1
    ;;
esac

PAYLOAD=$(jq -n \
  --arg model "gpt-image-2" \
  --arg prompt "$PROMPT" \
  --arg size "$SIZE" \
  --arg quality "high" \
  '{model:$model, prompt:$prompt, size:$size, quality:$quality, n:1}')

echo "[test] slug=$SLUG size=$SIZE quality=high"
echo "[test] calling /v1/images/generations..."

RESPONSE=$(curl -sS https://api.openai.com/v1/images/generations \
  -H "Authorization: Bearer $KEY" \
  -H "Content-Type: application/json" \
  -d "$PAYLOAD")

if echo "$RESPONSE" | jq -e '.error' >/dev/null 2>&1; then
  echo "[test] API error:" >&2
  echo "$RESPONSE" | jq '.error' >&2
  exit 1
fi

B64=$(echo "$RESPONSE" | jq -r '.data[0].b64_json // empty')
if [ -z "$B64" ]; then
  URL=$(echo "$RESPONSE" | jq -r '.data[0].url // empty')
  if [ -n "$URL" ]; then
    curl -sL "$URL" -o "$OUT_DIR/${SLUG}_v2.png"
  else
    echo "[test] no b64_json and no url in response" >&2
    echo "$RESPONSE" | jq '.' >&2
    exit 1
  fi
else
  echo "$B64" | base64 -d > "$OUT_DIR/${SLUG}_v2.png"
fi

printf '%s\n\n---\nsize: %s\nquality: high\nversion: brand-aligned-v2\n' "$PROMPT" "$SIZE" \
  > "$OUT_DIR/${SLUG}_v2.prompt.txt"

BYTES=$(stat -c%s "$OUT_DIR/${SLUG}_v2.png")
echo "[test] saved: $OUT_DIR/${SLUG}_v2.png  ($BYTES bytes)"
echo "[test] url:   https://sambla.ro/test-gpt-image-2/${SLUG}_v2.png"
