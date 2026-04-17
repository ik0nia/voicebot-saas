<?php

declare(strict_types=1);

namespace Tests\Support;

use PHPUnit\Framework\TestCase;

/**
 * Tiny golden-file snapshot helper. No external dependency.
 *
 * First run (file absent) records the expected output and fails the
 * test with an "initialized" message so the author reviews + commits the
 * file. Subsequent runs compare byte-exact.
 *
 * To regenerate (e.g. after an intentional change), delete the file or
 * run with the env var `CHATBOT_UPDATE_SNAPSHOTS=1`.
 */
final class Snapshots
{
    private const BASE_DIR = __DIR__ . '/../__snapshots__';

    /**
     * Match $actual against the file for the given test case + label.
     * If file doesn't exist, writes it and fails with a clear message.
     */
    public static function assertMatches(TestCase $test, mixed $actual, string $label): void
    {
        $path = self::pathFor($test, $label);
        $payload = self::encode($actual);
        $updating = (string) getenv('CHATBOT_UPDATE_SNAPSHOTS') === '1';

        if (!file_exists($path) || $updating) {
            self::write($path, $payload);
            if (!$updating) {
                $test::fail("Snapshot initialized: {$path}\nReview the file, commit it, then rerun.");
            }
            return;
        }

        $expected = file_get_contents($path);
        $test::assertSame(
            $expected,
            $payload,
            "Snapshot mismatch for {$label}\nExpected file: {$path}\n"
            . 'To regenerate intentionally: delete the file or run with CHATBOT_UPDATE_SNAPSHOTS=1.'
        );
    }

    private static function pathFor(TestCase $test, string $label): string
    {
        $class = str_replace('\\', '_', get_class($test));
        $method = method_exists($test, 'name') ? $test->name() : 'unknown';
        $safe = preg_replace('/[^A-Za-z0-9_.\-]/', '_', $label);
        return self::BASE_DIR . "/{$class}__{$method}__{$safe}.json";
    }

    private static function encode(mixed $value): string
    {
        return json_encode(
            $value,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) . "\n";
    }

    private static function write(string $path, string $payload): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($path, $payload);
    }
}
