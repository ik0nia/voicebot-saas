<?php
/*
 | Generates the Facebook + Instagram captions for each batch post that currently
 | has an image on disk. Infers the pattern from the filename so the captions
 | JSON always matches reality after a fresh image batch.
 |
 | Produces multi-paragraph copy (2-4 short paragraphs, blank lines between,
 | CTA on its own line) — mirrors the live-site tone.
 */

declare(strict_types=1);

$root = dirname(__DIR__);
chdir($root);

$envLine = shell_exec("grep '^OPENAI_API_KEY=' {$root}/.env | head -1");
$apiKey = trim(preg_replace('/^OPENAI_API_KEY=/', '', (string) $envLine), "\"' \n\r");
if ($apiKey === '') {
    fwrite(STDERR, "OPENAI_API_KEY missing\n"); exit(1);
}

$scenarios = [
    1  => ['niche' => 'contabil',   'message' => 'închidere de lună fără haos'],
    2  => ['niche' => 'salon',      'message' => 'zero programări pierdute în weekend'],
    3  => ['niche' => 'turism',     'message' => 'itinerariu personalizat în câteva minute'],
    4  => ['niche' => 'psiholog',   'message' => 'pacientul primește confirmarea în secunde'],
    5  => ['niche' => 'notar',      'message' => 'cererile programate se rezolvă în ziua respectivă'],
    6  => ['niche' => 'stomatolog', 'message' => 'pacienții își confirmă singuri programarea'],
    7  => ['niche' => 'restaurant', 'message' => 'rezervările se preiau chiar și în vârf de seară'],
    8  => ['niche' => 'auto',       'message' => 'clienții primesc status reparație fără să sune'],
    9  => ['niche' => 'avocat',     'message' => 'cererile inițiale se calificează înainte de întâlnire'],
    10 => ['niche' => 'cofetar',    'message' => 'comenzile pentru weekend se preiau pe WhatsApp'],
];

$brandContext = "Sambla — platformă românească de agenți AI care preiau apelurile, mesajele și programările pentru afaceri mici. Setup în 10 minute, fără halucinații, funcționează 24/7, vorbește limba română. Planuri de la 99€/lună.";

function callApi(string $apiKey, string $system, string $user): ?array {
    $payload = json_encode([
        'model' => 'gpt-4o-mini',
        'messages' => [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ],
        'response_format' => ['type' => 'json_object'],
        'temperature' => 0.75,
    ], JSON_UNESCAPED_UNICODE);
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ["Authorization: Bearer {$apiKey}", 'Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) {
        fwrite(STDERR, "http {$code}: " . mb_substr((string) $resp, 0, 300) . "\n");
        return null;
    }
    $data = json_decode((string) $resp, true);
    $content = $data['choices'][0]['message']['content'] ?? '';
    return json_decode((string) $content, true) ?: null;
}

function findPatternForBatch(int $index, string $niche, string $root): ?string {
    $glob = $root . "/public/test-gpt-image-2/batch_{$index}_*_{$niche}.png";
    $matches = glob($glob);
    if (!$matches) return null;
    $filename = basename($matches[0]);
    if (preg_match('/^batch_\d+_(.+?)_' . preg_quote($niche, '/') . '\.png$/', $filename, $m)) {
        return $m[1];
    }
    return null;
}

$system = <<<PROMPT
Ești copywriter nativ român, expert în social media pentru SaaS B2B cu targetare pe afaceri mici din România.

BRAND: {$brandContext}

Stil obligatoriu:
- Română idiomatică, naturală, umană. Fără traduceri literale din engleză. Fără reflexive redundante („să-mi programez" → „să programez"). Fără regionalisme învechite. Fără fluff marketing („descoperă acum", „transformă-ți afacerea").
- Diacritice corecte (ă, â, î, ș, ț). NU folosi typo-uri românești (ex.: NU „RESOLVATE", NU „aplicatie", NU „conversati").
- Nu menționa niciodată OpenAI, ChatGPT, Claude, Gemini, Meta, Google sau alți furnizori.

FORMATARE (CRITIC):
- Fiecare caption se împarte în **2-4 paragrafe scurte** separate prin linie goală (două \\n succesive).
- Fiecare paragraf are max 2-3 propoziții. Mobile-first.
- CTA se pune pe linia ei proprie, la final, eventual cu 1 emoji.
- Emoji folosit foarte puțin — max 1-3 pe tot textul pentru Facebook, max 2-4 pentru Instagram. Fără dumping de emoji.

Pentru fiecare cerere, returnezi JSON strict cu cheile:
- `facebook`: text Facebook de 80-180 cuvinte, conversațional, cu 2-4 paragrafe scurte și CTA pe linie separată la final. Fără hashtag-uri în text.
- `instagram`: caption Instagram de 40-90 cuvinte, mai vizual, cu 2-3 paragrafe scurte și CTA pe linie separată. Fără hashtag-uri în text.
- `hashtags`: array de 4-6 hashtag-uri RO relevante (fără „#"), lowercase, fără spații (ex: „afacerimici" nu „afaceri mici"), specifice nișei.
PROMPT;

$results = [];

foreach ($scenarios as $i => $s) {
    $pattern = findPatternForBatch($i, $s['niche'], $root);
    if (!$pattern) {
        echo "⚠ no image found for post {$i} ({$s['niche']}) — skipping\n";
        continue;
    }

    echo "Generating captions for post {$i} ({$pattern} × {$s['niche']})...\n";

    $user = "Generează un post social pentru Sambla, adresat afacerilor din nișa „{$s['niche']}\", pe tema: „{$s['message']}\". "
        . "Postul merge pe ambele platforme (Facebook + Instagram). Fă variantele potrivite fiecărei platforme. "
        . "Respectă formatarea cu paragrafe scurte separate de linie goală.";

    $r = callApi($apiKey, $system, $user);
    if (!$r) { $results[$i] = null; continue; }

    $results[$i] = array_merge($s, [
        'pattern' => $pattern,
        'facebook' => $r['facebook'] ?? null,
        'instagram' => $r['instagram'] ?? null,
        'hashtags' => $r['hashtags'] ?? [],
        'image_url' => "batch_{$i}_{$pattern}_{$s['niche']}.png",
    ]);

    $fb = (string) $results[$i]['facebook'];
    $ig = (string) $results[$i]['instagram'];
    echo "  FB (" . mb_strlen($fb) . " chars, " . substr_count($fb, "\n\n") . " paragraphs)\n";
    echo "  IG (" . mb_strlen($ig) . " chars, " . substr_count($ig, "\n\n") . " paragraphs)\n";
    echo "  #: " . implode(' ', array_map(fn($h) => "#{$h}", $results[$i]['hashtags'])) . "\n";
}

$outFile = $root . '/public/test-gpt-image-2/captions.json';
file_put_contents($outFile, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "\n✓ Saved to {$outFile}\n";
