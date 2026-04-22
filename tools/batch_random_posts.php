<?php
/*
 | Simulates what `php artisan social:generate-daily-batch 5` produces —
 | picks 5 weighted-random (pattern, niche) pairs, runs the RomanianPromptComposer
 | on the key message, renders the final prompt, and calls gpt-image-2 with logo-ref.
 | Bypasses Laravel bootstrap (local dev has no DB/Redis).
 |
 | Output: 5 PNGs under public/test-gpt-image-2/batch_N_<pattern>_<niche>.png
 */

declare(strict_types=1);

$root = dirname(__DIR__);
chdir($root);

$config = require $root . '/config/social-image-patterns.php';

$envLine = shell_exec("grep '^OPENAI_API_KEY=' {$root}/.env | head -1");
$apiKey = trim(preg_replace('/^OPENAI_API_KEY=/', '', (string) $envLine), "\"' \n\r");
if ($apiKey === '') {
    fwrite(STDERR, "OPENAI_API_KEY missing from .env\n");
    exit(1);
}

$logoPath = $root . '/public/images/logo-icon.png';
if (!file_exists($logoPath)) {
    fwrite(STDERR, "logo not found: {$logoPath}\n");
    exit(1);
}

// ─── weighted random pattern pick (matches PatternCatalog::pickWeighted) ────
function pickPattern(array $patterns): string {
    $total = 0.0;
    foreach ($patterns as $slug => $def) {
        $total += (float) ($def['weight'] ?? 1.0);
    }
    $roll = mt_rand() / mt_getrandmax() * $total;
    $acc = 0.0;
    foreach ($patterns as $slug => $def) {
        $acc += (float) ($def['weight'] ?? 1.0);
        if ($roll <= $acc) return $slug;
    }
    return array_key_first($patterns);
}

// ─── 5 semi-realistic (niche, key_message) scenarios ─────────────────────────
$scenarios = [
    ['niche' => 'contabil',    'message' => 'închidere de lună fără haos'],
    ['niche' => 'salon',       'message' => 'zero programări pierdute în weekend'],
    ['niche' => 'turism',      'message' => 'itinerariu personalizat în câteva minute'],
    ['niche' => 'psiholog',    'message' => 'pacientul primește confirmarea în secunde'],
    ['niche' => 'notar',       'message' => 'cererile programate se rezolvă în ziua respectivă'],
];

// Force variety: shuffle + guarantee 5 distinct patterns where possible
$usedPatterns = [];

// ─── helpers ─────────────────────────────────────────────────────────────────
function composeCopy(array $scenario, array $pattern, string $patternSlug, string $apiKey): ?array {
    $needs = $pattern['required_copy'] ?? [];
    if (!$needs) return null;

    $system = "Ești un copywriter nativ român, expert în marketing digital premium pentru SaaS B2B (platformă de agenți AI care preiau apelurile și mesajele pentru business-uri mici).\n"
        . "Stil obligatoriu: română idiomatică, modernă, concisă, fără traduceri literale, fără reflexive redundante („să-mi programez\" → „să programez\"), fără regionalisme învechite. Diacritice corecte (ă, â, î, ș, ț).\n"
        . "Nu menționa OpenAI, ChatGPT, Claude, Gemini sau alți furnizori AI. Fără lăudăroșenie marketing.\n"
        . "Răspunde DOAR cu un JSON object. Cheile sunt exact cele cerute. Valorile sunt stringuri scurte (max 80 caractere) în română nativă, fără ghilimele duble la început/sfârșit dacă cheia nu se numește `pull_quote` sau `attribution`.";

    $user = "Compune copy pentru o postare social-media premium.\n"
        . "Nișă: {$scenario['niche']}\n"
        . "Pattern: {$patternSlug}\n"
        . "Mesaj cheie: {$scenario['message']}\n\n"
        . "Returnează JSON cu EXACT aceste chei:\n"
        . json_encode(array_fill_keys($needs, '...'), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    $payload = json_encode([
        'model' => 'gpt-5.4',
        'messages' => [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ],
        'response_format' => ['type' => 'json_object'],
        'temperature' => 0.4,
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ["Authorization: Bearer {$apiKey}", 'Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        fwrite(STDERR, "[composer] http {$httpCode}: " . mb_substr((string) $response, 0, 200) . "\n");
        return null;
    }
    $data = json_decode((string) $response, true);
    $content = $data['choices'][0]['message']['content'] ?? '';
    $parsed = json_decode((string) $content, true);
    return is_array($parsed) ? array_intersect_key($parsed, array_flip($needs)) : null;
}

function renderPrompt(array $config, array $pattern, string $niche, array $copy): string {
    $resolve = fn(string $map) => $config[$map][$niche] ?? $config[$map]['default'] ?? '';
    $copy['subject_hint'] = $resolve('niche_subjects');
    $copy['subject_hint_before'] = $resolve('niche_subjects_before');
    $copy['subject_hint_after'] = $copy['subject_hint_before'];
    $copy['niche_graphics'] = $resolve('niche_graphic_elements');
    $copy['niche_scene'] = $resolve('niche_scene');
    $copy['niche_label'] = $resolve('niche_labels');
    $copy['niche_accent'] = $resolve('niche_accents') ?: '#DC2626';
    $copy['sambla_mark'] = $config['sambla_mark'] ?? '';

    $rendered = $pattern['template'];
    foreach ($copy as $k => $v) {
        $rendered = str_replace('{' . $k . '}', (string) $v, $rendered);
    }
    // Second pass — niche_label is often embedded in default_copy values.
    $rendered = str_replace(
        ['{niche_label}', '{niche_graphics}', '{niche_scene}', '{sambla_mark}', '{niche_accent}'],
        [$copy['niche_label'], $copy['niche_graphics'], $copy['niche_scene'], $copy['sambla_mark'], $copy['niche_accent']],
        $rendered
    );

    return implode("\n\n", [
        $config['brand_preamble'],
        $config['safe_zone_rule'],
        "CANVAS: portrait aspect ratio {$pattern['aspect_ratio']}.",
        $rendered,
        $config['text_rule'],
        $config['do_not_rule'],
    ]);
}

function generateImage(string $prompt, string $size, string $apiKey, string $logoPath): ?string {
    $ch = curl_init('https://api.openai.com/v1/images/edits');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'model' => 'gpt-image-2',
            'prompt' => $prompt,
            'size' => $size,
            'quality' => 'high',
            'n' => 1,
            'image' => new CURLFile($logoPath, 'image/png', 'logo-icon.png'),
        ],
        CURLOPT_HTTPHEADER => ["Authorization: Bearer {$apiKey}"],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 300,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) {
        fwrite(STDERR, "[gen] http {$code}: " . mb_substr((string) $resp, 0, 300) . "\n");
        return null;
    }
    $data = json_decode((string) $resp, true);
    return $data['data'][0]['b64_json'] ?? null;
}

// ─── main ────────────────────────────────────────────────────────────────────
$outDir = $root . '/public/test-gpt-image-2';
if (!is_dir($outDir)) mkdir($outDir, 0755, true);

foreach ($scenarios as $i => $scenario) {
    $index = $i + 1;

    // Pick a pattern we haven't used yet, if possible
    $attempts = 0;
    do {
        $patternSlug = pickPattern($config['patterns']);
        $attempts++;
    } while (in_array($patternSlug, $usedPatterns, true) && $attempts < 20);
    $usedPatterns[] = $patternSlug;

    $pattern = $config['patterns'][$patternSlug];
    $size = match ($pattern['aspect_ratio']) {
        '1:1' => '1024x1024',
        '4:5' => '1024x1280',
        '9:16' => '1024x1792',
        '2:3' => '1024x1536',
        default => '1024x1280',
    };

    echo "\n========= POST {$index}/5 =========\n";
    echo "Pattern:  {$patternSlug} ({$pattern['category']}, w={$pattern['weight']})\n";
    echo "Niche:    {$scenario['niche']}\n";
    echo "Message:  {$scenario['message']}\n";
    echo "Size:     {$size}\n";

    echo "[1/3] composing RO copy via GPT-5.4...\n";
    $copy = composeCopy($scenario, $pattern, $patternSlug, $apiKey);
    if ($copy) {
        foreach ($copy as $k => $v) echo "      {$k}: {$v}\n";
    } else {
        echo "      [composer failed — falling back to pattern defaults]\n";
        $copy = [];
    }
    $copy = array_merge($pattern['default_copy'] ?? [], $copy);

    echo "[2/3] rendering final prompt...\n";
    $prompt = renderPrompt($config, $pattern, $scenario['niche'], $copy);
    echo "      prompt length: " . mb_strlen($prompt) . " chars\n";

    echo "[3/3] generating via gpt-image-2 + logo-ref...\n";
    $start = microtime(true);
    $b64 = generateImage($prompt, $size, $apiKey, $logoPath);
    $elapsed = round(microtime(true) - $start, 1);

    if (!$b64) {
        echo "      ❌ FAILED\n";
        continue;
    }

    $outFile = $outDir . "/batch_{$index}_{$patternSlug}_{$scenario['niche']}.png";
    file_put_contents($outFile, base64_decode($b64));
    $bytes = filesize($outFile);
    echo "      ✓ {$elapsed}s, " . number_format($bytes) . " bytes\n";
    echo "      URL: https://sambla.ro/test-gpt-image-2/batch_{$index}_{$patternSlug}_{$scenario['niche']}.png\n";
}

echo "\n========= DONE =========\n";
