<?php
// Palma — endpoint citire (Claude API).
// Custom PHP, fără Laravel. Cheia se citește direct din /var/www/html/.env.

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST only']);
    exit;
}

$raw = file_get_contents('php://input') ?: '';
if (strlen($raw) > 6_000_000) {
    http_response_code(413);
    echo json_encode(['error' => 'Payload too large']);
    exit;
}
$body = json_decode($raw, true);
if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$m = $body['measurements'] ?? [];
$frame = is_string($body['frame'] ?? null) ? $body['frame'] : null;

// ─── Load API key from .env (without Laravel) ─────────────────────────────
function load_env_var(string $key): ?string {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (['/var/www/html/.env', __DIR__ . '/../../.env'] as $path) {
            if (!is_readable($path)) continue;
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = ltrim($line);
                if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
                [$k, $v] = explode('=', $line, 2);
                $cache[trim($k)] = trim($v, " \t\"'");
            }
            break;
        }
    }
    return $cache[$key] ?? (getenv($key) ?: null);
}

$apiKey = load_env_var('ANTHROPIC_API_KEY');

// ─── Build Claude prompt — măsurători reale ca să sune personalizat ───────
$measurementText = sprintf(
    "Măsurători detectate de viziune computerizată asupra palmei:\n"
    . "- Linia inimii: %.2f cm (lungime totală pe baza degetelor)\n"
    . "- Linia minții: %.2f cm (axă mediană transversală)\n"
    . "- Linia vieții: %.2f cm (arc între degetul mare și încheietură)\n"
    . "- Raport palmă (lățime/înălțime): %.2f\n"
    . "- Unghiul degetului mare: %.1f° față de palmă\n"
    . "- Mâna detectată: %s",
    (float) ($m['heartLineLength']  ?? 7.0),
    (float) ($m['headLineLength']   ?? 6.5),
    (float) ($m['lifeLineLength']   ?? 8.0),
    (float) ($m['palmRatio']        ?? 0.9),
    (float) ($m['thumbAngle']       ?? 45.0),
    (string) ($m['handedness']      ?? 'necunoscută'),
);

$systemPrompt = <<<SYS
Ești un cititor în palmă cu sute de ani de tradiție, dar care înțelege și știință modernă.
Vorbești în română, cu un ton cald, mistic dar credibil — nu kitsch.
Folosești MĂSURĂTORILE OBIECTIVE primite (în cm, raporturi, unghiuri) ca puncte de plecare reale,
nu doar decor: dacă linia inimii e lungă → menționează clar; dacă raportul palmei e ≈1 → palmă pătrată = pragmatism etc.

Returnezi STRICT un JSON valid (fără markdown, fără ```json), cu structura:
{
  "scores": {
    "Vitalitate": <int 0-100>,
    "Creativitate": <int 0-100>,
    "Empatie": <int 0-100>,
    "Intuiție": <int 0-100>,
    "Rezistență": <int 0-100>
  },
  "sections": [
    {"title": "Personalitate", "body": "<2-4 fraze, conectat la măsurători>"},
    {"title": "Dragoste", "body": "<2-4 fraze>"},
    {"title": "Carieră", "body": "<2-4 fraze>"},
    {"title": "Sănătate", "body": "<2-4 fraze>"},
    {"title": "Predicții", "body": "<2-4 fraze, orizont 3-12 luni, concrete dar nu alarmiste>"}
  ],
  "tagline": "<o frază scurtă, poetică, ca subtitlu pe ecranul de rezultat>"
}

Reguli stricte:
- Nu inventa măsurători noi în text — folosește-le pe cele primite, cu valori plauzibile.
- Nu folosi clișee gen „vei călători" sau „vei primi bani neașteptați".
- Nu menționa AI, model, OpenAI, Anthropic.
- Nu da sfaturi medicale.
- Răspunsul e exclusiv JSON.
SYS;

$userPrompt = $measurementText . "\n\nGenerează citirea palmei ca JSON.";

// ─── Fallback mock dacă nu avem cheie ─────────────────────────────────────
function mock_response(array $m): array {
    $seed = (int) round(
        (float) ($m['heartLineLength'] ?? 7) * 100
      + (float) ($m['headLineLength']  ?? 6) * 10
      + (float) ($m['lifeLineLength']  ?? 8)
    );
    mt_srand($seed);
    return [
        'ok'   => true,
        'mock' => true,
        'tagline' => 'Liniile spun mai mult decât crezi.',
        'scores' => [
            'Vitalitate'   => mt_rand(72, 96),
            'Creativitate' => mt_rand(68, 95),
            'Empatie'      => mt_rand(70, 94),
            'Intuiție'     => mt_rand(65, 93),
            'Rezistență'   => mt_rand(70, 92),
        ],
        'sections' => [
            ['title' => 'Personalitate', 'body' => 'Liniile palmei tale arată un echilibru între rațiune și instinct. Linia minții bine conturată indică o gândire structurată, dar curbura ei către baza palmei sugerează că nu te oprești la analiză — cauți și sensul.'],
            ['title' => 'Dragoste',      'body' => 'Linia inimii are o traiectorie deschisă, ceea ce indică o capacitate reală de a iubi fără rezerve. În următoarele 6–9 luni e probabil să apară o conexiune semnificativă sau să se aprofundeze una existentă.'],
            ['title' => 'Carieră',       'body' => 'Degetul mare cu unghi deschis indică independență. Liniile auxiliare către Mercur sugerează talent pentru comunicare și negociere — un punct forte pe care ar fi păcat să-l ignori.'],
            ['title' => 'Sănătate',      'body' => 'Linia vieții lungă și clară este un semn bun. Atenția se concentrează în zona stresului mental — odihna și pauzele scurte de respirație îți vor stabiliza nivelul de energie pe termen lung.'],
            ['title' => 'Predicții',     'body' => 'Următoarele 3 luni aduc o decizie importantă legată de un proiect personal. Nu o lua în grabă, dar nu o amâna nici excesiv — răspunsul îl ai deja, chiar dacă încă nu l-ai formulat.'],
        ],
    ];
}

if (!$apiKey) {
    echo json_encode(mock_response($m), JSON_UNESCAPED_UNICODE);
    exit;
}

// ─── Call Claude API ──────────────────────────────────────────────────────
$content = [
    ['type' => 'text', 'text' => $userPrompt],
];

// Atașăm cadrul ca image dacă vine ca data URL valid (jpeg/png).
if ($frame && preg_match('#^data:image/(jpeg|png|webp);base64,([A-Za-z0-9+/=]+)$#', $frame, $mm)) {
    $content[] = [
        'type'   => 'image',
        'source' => [
            'type'       => 'base64',
            'media_type' => 'image/' . $mm[1],
            'data'       => $mm[2],
        ],
    ];
}

$payload = [
    'model'       => 'claude-sonnet-4-5',
    'max_tokens'  => 1500,
    'temperature' => 0.85,
    'system'      => $systemPrompt,
    'messages'    => [
        ['role' => 'user', 'content' => $content],
    ],
];

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
    CURLOPT_HTTPHEADER     => [
        'content-type: application/json',
        'x-api-key: ' . $apiKey,
        'anthropic-version: 2023-06-01',
    ],
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_CONNECTTIMEOUT => 10,
]);
$resp = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
$err = curl_error($ch);
curl_close($ch);

if ($resp === false || $status >= 400) {
    error_log("[palma] Claude API err status=$status err=$err body=" . substr((string) $resp, 0, 500));
    $out = mock_response($m);
    $out['fallback_reason'] = "Claude API ($status)";
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode((string) $resp, true);
$text = $data['content'][0]['text'] ?? '';

// Strip eventual ```json wrapper dacă modelul s-a abătut.
$text = trim($text);
if (str_starts_with($text, '```')) {
    $text = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $text);
}

$reading = json_decode((string) $text, true);
if (!is_array($reading) || !isset($reading['sections'])) {
    error_log('[palma] Claude returned non-JSON: ' . substr($text, 0, 300));
    $out = mock_response($m);
    $out['fallback_reason'] = 'Răspuns AI invalid';
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    exit;
}

$reading['ok'] = true;
$reading['mock'] = false;
$reading['measurements'] = $m;

echo json_encode($reading, JSON_UNESCAPED_UNICODE);
