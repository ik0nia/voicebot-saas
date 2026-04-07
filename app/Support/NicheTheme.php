<?php

namespace App\Support;

final class NicheTheme
{
    public static function get(string $key): array
    {
        $themes = config('niche-themes', []);
        return $themes[$key] ?? $themes['red'];
    }
}
