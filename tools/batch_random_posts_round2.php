<?php
/*
 | Second batch of 5 posts with DIFFERENT niches than round 1, using the fully
 | expanded 18-pattern catalog + niche_accent color system. Saves under
 | batch_6_* … batch_10_* so the gallery shows 10 unique posts total.
 */

declare(strict_types=1);

$root = dirname(__DIR__);
chdir($root);

$config = require $root . '/config/social-image-patterns.php';

$envLine = shell_exec("grep '^OPENAI_API_KEY=' {$root}/.env | head -1");
$apiKey = trim(preg_replace('/^OPENAI_API_KEY=/', '', (string) $envLine), "\"' \n\r");
if ($apiKey === '') { fwrite(STDERR, "OPENAI_API_KEY missing\n"); exit(1); }

$logoPath = $root . '/public/images/logo-icon.png';

function pickPattern(array $patterns): string {
    $total = 0.0;
    foreach ($patterns as $def) { $total += (float) ($def['weight'] ?? 1.0); }
    $roll = mt_rand() / mt_getrandmax() * $total;
    $acc = 0.0;
    foreach ($patterns as $slug => $def) {
        $acc += (float) ($def['weight'] ?? 1.0);
        if ($roll <= $acc) return $slug;
    }
    return array_key_first($patterns);
}

// Round 2 scenarios — distinct niches to show niche-accent palette variety
$scenarios = [
    6  => ['niche' => 'stomatolog', 'message' => 'pacienții își confirmă singuri programarea'],
    7  => ['niche' => 'restaurant', 'message' => 'rezervările se preiau chiar și în vârf de seară'],
    8  => ['niche' => 'auto',        'message' => 'clienții primesc status reparație fără să sune'],
    9  => ['niche' => 'avocat',      'message' => 'cererile inițiale se calificează înainte de întâlnire'],
    10 => ['niche' => 'cofetar',     'message' => 'comenzile pentru weekend se preiau pe WhatsApp'],
];

$usedPatterns = [];

function composeCopy(array $scenario, array $pattern, string $patternSlug, string $apiKey): ?array {
    $needs = $pattern['required_copy'] ?? [];
    if (!$needs) return null;

    $system = "Ești copywriter nativ român, expert în marketing digital premium pentru SaaS B2B (platformă de agenți AI pentru afaceri mici).\n"
        . "Stil: română idiomatică, modernă, concisă. Fără traduceri literale. Fără reflexive redundante. Diacritice corecte.\n"
        . "Nu menționa OpenAI, ChatGPT, Claude, Gemini sau alți furnizori AI.\n"
        . "Răspunde DOAR cu JSON object. Cheile cerute exact. Valori scurte (max 80 caractere) în română nativă.";

    $user = "Compune copy pentru o postare social-media premium.\n"
        . "Nișă client: {$scenario['niche']}\n"
        . "Pattern: {$patternSlug}\n"
        . "Mesaj cheie: {$scenario['message']}\n\n"
        . "Returnează JSON cu EXACT aceste chei (nimic în plus):\n"
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
        CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ["Authorization: Bearer {$apiKey}", 'Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    $data = json_decode((string) $resp, true);
    $content = $data['choices'][0]['message']['content'] ?? '';
    $parsed = json_decode((string) $content, true);
    return is_array($parsed) ? array_intersect_key($parsed, array_flip($needs)) : null;
}

function renderPrompt(array $config, array $pattern, string $niche, array $copy): string {
    $resolve = fn(string $map, string $fallback = '') => $config[$map][$niche] ?? $config[$map]['default'] ?? $fallback;
    $copy['subject_hint'] = $resolve('niche_subjects');
    $copy['subject_hint_before'] = $resolve('niche_subjects_before');
    $copy['subject_hint_after'] = $copy['subject_hint_before'];
    $copy['niche_graphics'] = $resolve('niche_graphic_elements');
    $copy['niche_scene'] = $resolve('niche_scene');
    $copy['niche_label'] = $resolve('niche_labels');
    $copy['niche_accent'] = $resolve('niche_accents', '#DC2626');
    $copy['sambla_mark'] = $config['sambla_mark'] ?? '';

    $rendered = $pattern['template'];
    foreach ($copy as $k => $v) { $rendered = str_replace('{' . $k . '}', (string) $v, $rendered); }
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
            'model' => 'gpt-image-2', 'prompt' => $prompt, 'size' => $size,
            'quality' => 'high', 'n' => 1,
            'image' => new CURLFile($logoPath, 'image/png', 'logo-icon.png'),
        ],
        CURLOPT_HTTPHEADER => ["Authorization: Bearer {$apiKey}"],
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 300,
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

$outDir = $root . '/public/test-gpt-image-2';
if (!is_dir($outDir)) mkdir($outDir, 0755, true);

// Exclude patterns used in round 1 so round 2 definitely shows different ones
$round1Patterns = ['split_before_after', 'phone_mockup_focus', 'vertical_timeline_flow', 'poster_typography_hero', 'icon_grid_features'];

foreach ($scenarios as $idx => $scenario) {
    $attempts = 0;
    do {
        $patternSlug = pickPattern($config['patterns']);
        $attempts++;
    } while ((in_array($patternSlug, $usedPatterns, true) || in_array($patternSlug, $round1Patterns, true)) && $attempts < 30);
    $usedPatterns[] = $patternSlug;

    $pattern = $config['patterns'][$patternSlug];
    $size = match ($pattern['aspect_ratio']) {
        '1:1' => '1024x1024',
        '4:5' => '1024x1280',
        '9:16' => '1024x1792',
        '2:3' => '1024x1536',
        default => '1024x1280',
    };

    echo "\n========= POST {$idx} (round 2) =========\n";
    echo "Pattern:  {$patternSlug} ({$pattern['category']}, w={$pattern['weight']})\n";
    echo "Niche:    {$scenario['niche']}\n";
    echo "Accent:   " . ($config['niche_accents'][$scenario['niche']] ?? '#DC2626') . "\n";
    echo "Message:  {$scenario['message']}\n";

    echo "[1/3] composer...\n";
    $copy = composeCopy($scenario, $pattern, $patternSlug, $apiKey) ?: [];
    $copy = array_merge($pattern['default_copy'] ?? [], $copy);

    echo "[2/3] render...\n";
    $prompt = renderPrompt($config, $pattern, $scenario['niche'], $copy);

    echo "[3/3] gpt-image-2...\n";
    $start = microtime(true);
    $b64 = generateImage($prompt, $size, $apiKey, $logoPath);
    $elapsed = round(microtime(true) - $start, 1);

    if (!$b64) { echo "  ❌ FAILED\n"; continue; }

    $outFile = $outDir . "/batch_{$idx}_{$patternSlug}_{$scenario['niche']}.png";
    file_put_contents($outFile, base64_decode($b64));
    echo "  ✓ {$elapsed}s — " . number_format(filesize($outFile)) . " bytes\n";
    echo "  URL: https://sambla.ro/test-gpt-image-2/batch_{$idx}_{$patternSlug}_{$scenario['niche']}.png\n";
}

echo "\n========= ROUND 2 DONE =========\n";
