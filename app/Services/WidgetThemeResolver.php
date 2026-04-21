<?php

namespace App\Services;

class WidgetThemeResolver
{
    /**
     * Resolve the effective theme tokens for a channel's config payload.
     *
     * Selection order:
     *   1. config['theme_preset'] matches a catalog entry → use that preset.
     *   2. config['color'] is a valid hex → treat as custom (accent-only;
     *      accent_soft derived by lightening, bubble_radius from default).
     *   3. Otherwise → fall back to the config('widget-themes.default') preset.
     *
     * @param  array<string, mixed>  $channelConfig
     * @return array{accent: string, accent_soft: string, bubble_radius: string, preset: string}
     */
    public function resolve(array $channelConfig): array
    {
        $presets = config('widget-themes.presets', []);
        $defaultKey = config('widget-themes.default', 'coral');

        $presetKey = $channelConfig['theme_preset'] ?? null;

        if ($presetKey && isset($presets[$presetKey])) {
            $preset = $presets[$presetKey];

            return [
                'accent' => $preset['accent'],
                'accent_soft' => $preset['accent_soft'],
                'bubble_radius' => $preset['bubble_radius'],
                'preset' => $presetKey,
            ];
        }

        $customColor = $channelConfig['color'] ?? null;
        if (is_string($customColor) && preg_match('/^#[0-9a-fA-F]{6}$/', $customColor)) {
            $defaultRadius = $presets[$defaultKey]['bubble_radius'] ?? '16px';

            return [
                'accent' => $customColor,
                'accent_soft' => $this->lighten($customColor, 0.18),
                'bubble_radius' => $defaultRadius,
                'preset' => 'custom',
            ];
        }

        $fallback = $presets[$defaultKey] ?? [
            'accent' => '#991b1b',
            'accent_soft' => '#dc2626',
            'bubble_radius' => '16px',
        ];

        return [
            'accent' => $fallback['accent'],
            'accent_soft' => $fallback['accent_soft'],
            'bubble_radius' => $fallback['bubble_radius'],
            'preset' => $defaultKey,
        ];
    }

    /**
     * Return the preset catalog as a list ready for the editor UI.
     *
     * @return array<int, array{key: string, name: string, description: string, accent: string, accent_soft: string, bubble_radius: string}>
     */
    public function catalog(): array
    {
        $presets = config('widget-themes.presets', []);
        $list = [];

        foreach ($presets as $key => $preset) {
            $list[] = [
                'key' => $key,
                'name' => $preset['name'],
                'description' => $preset['description'],
                'accent' => $preset['accent'],
                'accent_soft' => $preset['accent_soft'],
                'bubble_radius' => $preset['bubble_radius'],
            ];
        }

        return $list;
    }

    /**
     * Lighten a hex color by the given ratio (0..1). Used when the tenant
     * picks a custom accent — we need a matching gradient end that isn't
     * white, so we pull each channel toward 255 by `ratio`.
     */
    private function lighten(string $hex, float $ratio): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return '#' . $hex;
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = (int) min(255, $r + (255 - $r) * $ratio);
        $g = (int) min(255, $g + (255 - $g) * $ratio);
        $b = (int) min(255, $b + (255 - $b) * $ratio);

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}
