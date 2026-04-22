#!/usr/bin/env bash
# Test gpt-image-2 using /v1/images/edits with our brand icon as a reference image.
# Lets the model see the actual logo shape/color instead of us composing it in post.

set -euo pipefail

cd "$(dirname "$0")/.."

KEY=$(grep '^OPENAI_API_KEY=' .env | head -1 | cut -d= -f2- | tr -d '"'"'")

SLUG="${1:-swiss_poster_logo}"
LOGO="public/images/logo-icon.png"
OUT_DIR="public/test-gpt-image-2"
mkdir -p "$OUT_DIR"

if [ ! -f "$LOGO" ]; then
  echo "[test] logo not found: $LOGO" >&2
  exit 1
fi

case "$SLUG" in
  swiss_poster_logo)
    SIZE="1024x1536"
    PROMPT='REFERENCE IMAGE: use the provided brand icon exactly — preserve its squircle shape, coral red color and mouth-like mark. Do NOT redraw or reinterpret. Place it verbatim.

CANVAS: 1024x1536, portrait 4:5, Instagram feed post.

CRITICAL SAFE ZONE: Every letter, the logo, and the hairline rule must sit inside the central 70% of the canvas, with at least 15% empty padding from the top, 15% from the bottom, 12% from the left and 12% from the right. The outer ring must be pure background — no glyphs, no logo, nothing near the canvas edges. Instagram crops these edges.

BACKGROUND: cream paper #faf6ef with subtle offset-print texture and faint risograph grain.

HERO TYPOGRAPHY (inside safe zone, top 75% of safe area): Romanian headline in heavy condensed grotesk (Druk Wide Super / Inter Display Black), deep navy #0f172a, razor-sharp kerning, three tight-stacked lines, each line ~25% of safe-area height: "AGENTUL" / "TĂU AI NU" / "DOARME." Letters enormous but respecting the safe boundary.

ACCENT: one solid coral red circle #dc2626 the size of lowercase x-height, placed as a period after DOARME on the baseline.

LOGO PLACEMENT (bottom-right of safe zone, not canvas edge): place the reference brand icon at roughly 9% of canvas width, anchored to the bottom-right of the safe zone (not the canvas corner). Maintain its original color and shape.

CAPTION (bottom-left of safe zone, aligned with the logo baseline): thin navy hairline rule spanning from left safe boundary to just before the logo, with one small monospace line just above it: "RĂSPUNDE 24/7 · ROMÂNIA".

DO NOT: add any photography, illustration, additional shapes, drop shadows, 3D effects. Do not place anything in the outer 15% canvas margin.

STYLE: Pentagram × Apple, editorial magazine cover, confident and minimal.'
    ;;
  brand_billboard_logo)
    SIZE="1024x1536"
    PROMPT='Use the provided image as the exact brand icon — preserve shape and coral red color, do not redraw. Compose a high-end outdoor billboard mockup, portrait 4:5. The billboard fills ~90% of the canvas against a soft dusk sky gradient (#fef3e7 to #f5c7a9). Billboard surface is matte off-white (#f8f5ee). Place the brand icon centered in the top-third at about 14% of canvas width. Below it, a single massive Romanian phrase set in heavy modern serif (GT Super / Canela Black), deep navy (#0f172a), two tight lines: "VOCEA TA." / "PRELUNGITĂ." — letters enormous, confident. A single tiny coral red underline under "TA". Subtle shadow under the billboard frame suggesting real outdoor installation. Photorealistic billboard mockup, architectural photography vibe. No other elements, no text besides the headline.'
    ;;
  *)
    echo "unknown slug: $SLUG" >&2
    exit 1
    ;;
esac

echo "[test] slug=$SLUG size=$SIZE logo=$LOGO"
echo "[test] prompt: $PROMPT"
echo "[test] calling /v1/images/edits..."

RESPONSE=$(curl -sS https://api.openai.com/v1/images/edits \
  -H "Authorization: Bearer $KEY" \
  -F model="gpt-image-2" \
  -F image="@$LOGO" \
  -F prompt="$PROMPT" \
  -F size="$SIZE" \
  -F quality="high" \
  -F n=1)

if echo "$RESPONSE" | jq -e '.error' >/dev/null 2>&1; then
  echo "[test] API error:" >&2
  echo "$RESPONSE" | jq '.error' >&2
  exit 1
fi

B64=$(echo "$RESPONSE" | jq -r '.data[0].b64_json // empty')
if [ -z "$B64" ]; then
  URL=$(echo "$RESPONSE" | jq -r '.data[0].url // empty')
  if [ -n "$URL" ]; then
    curl -sL "$URL" -o "$OUT_DIR/$SLUG.png"
  else
    echo "[test] no b64_json and no url in response" >&2
    echo "$RESPONSE" | jq '.' >&2
    exit 1
  fi
else
  echo "$B64" | base64 -d > "$OUT_DIR/$SLUG.png"
fi

printf '%s\n\n---\nsize: %s\nquality: high\nendpoint: /v1/images/edits\nreference_image: %s\n' \
  "$PROMPT" "$SIZE" "$LOGO" > "$OUT_DIR/$SLUG.prompt.txt"

BYTES=$(stat -c%s "$OUT_DIR/$SLUG.png")
echo "[test] saved: $OUT_DIR/$SLUG.png  ($BYTES bytes)"
echo "[test] url:   https://sambla.ro/test-gpt-image-2/$SLUG.png"
