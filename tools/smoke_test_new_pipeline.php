<?php
/*
 | Standalone smoke test for the new social-image pipeline.
 | Bypasses Laravel bootstrap (local dev has no DB/Redis access) and exercises:
 |   1. PatternCatalog render logic on the config
 |   2. GptImage2 HTTP call via raw curl/Http
 | Run:  php tools/smoke_test_new_pipeline.php <pattern_slug> [niche]
 */

declare(strict_types=1);

$pattern = $argv[1] ?? 'bento_editorial_portrait';
$niche = $argv[2] ?? 'veterinar';

$root = dirname(__DIR__);
chdir($root);

$config = require $root . '/config/social-image-patterns.php';

if (!isset($config['patterns'][$pattern])) {
    fwrite(STDERR, "Unknown pattern: {$pattern}\nAvailable: " . implode(', ', array_keys($config['patterns'])) . "\n");
    exit(1);
}

$p = $config['patterns'][$pattern];
$subjectHint = $config['niche_subjects'][$niche] ?? $config['niche_subjects']['default'];
$subjectHintBefore = $config['niche_subjects_before'][$niche] ?? $config['niche_subjects_before']['default'];

$copy = array_merge($p['default_copy'] ?? [], [
    'subject_hint' => $subjectHint,
    'subject_hint_before' => $subjectHintBefore,
    'subject_hint_after' => $subjectHintBefore,
]);

$rendered = $p['template'];
foreach ($copy as $k => $v) {
    $rendered = str_replace('{' . $k . '}', (string) $v, $rendered);
}

$finalPrompt = implode("\n\n", [
    $config['brand_preamble'],
    $config['safe_zone_rule'],
    "CANVAS: portrait aspect ratio {$p['aspect_ratio']}.",
    $rendered,
    $config['text_rule'],
    $config['do_not_rule'],
]);

echo "--- RENDERED PROMPT ({$pattern} / {$niche}) ---\n";
echo $finalPrompt . "\n";
echo "--- END PROMPT (" . mb_strlen($finalPrompt) . " chars) ---\n\n";

// Call gpt-image-2.
$envLine = shell_exec("grep '^OPENAI_API_KEY=' {$root}/.env | head -1");
if (!$envLine) {
    fwrite(STDERR, "OPENAI_API_KEY not found in .env\n");
    exit(1);
}
$key = trim(preg_replace('/^OPENAI_API_KEY=/', '', $envLine), "\"' \n\r");

$size = match ($p['aspect_ratio']) {
    '1:1' => '1024x1024',
    '16:9', '4:3', '3:2' => '1536x1024',
    default => '1024x1536',
};

$payload = json_encode([
    'model' => 'gpt-image-2',
    'prompt' => $finalPrompt,
    'size' => $size,
    'quality' => 'high',
    'n' => 1,
], JSON_UNESCAPED_UNICODE);

echo "Calling gpt-image-2 (size={$size})...\n";
$start = microtime(true);

$ch = curl_init('https://api.openai.com/v1/images/generations');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$key}",
        'Content-Type: application/json',
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 300,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$elapsed = round(microtime(true) - $start, 1);

if ($httpCode !== 200) {
    echo "API error (HTTP {$httpCode}):\n{$response}\n";
    exit(1);
}

$data = json_decode($response, true);
$b64 = $data['data'][0]['b64_json'] ?? null;
$url = $data['data'][0]['url'] ?? null;

if (!$b64 && $url) {
    $b64 = base64_encode(file_get_contents($url));
}
if (!$b64) {
    echo "No image in response:\n{$response}\n";
    exit(1);
}

$outDir = $root . '/public/test-gpt-image-2';
if (!is_dir($outDir)) mkdir($outDir, 0755, true);
$outFile = $outDir . "/pipeline_{$pattern}_{$niche}.png";
file_put_contents($outFile, base64_decode($b64));

$bytes = filesize($outFile);
echo "✓ Generated in {$elapsed}s\n";
echo "  File: {$outFile} ({$bytes} bytes)\n";
echo "  URL:  https://sambla.ro/test-gpt-image-2/pipeline_{$pattern}_{$niche}.png\n";
