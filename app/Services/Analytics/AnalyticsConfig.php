<?php

namespace App\Services\Analytics;

use App\Models\PlatformSetting;

/**
 * Runtime resolver for marketing/analytics IDs. Reads from
 * PlatformSetting so admins can tweak IDs without a deploy, but
 * falls back to config('analytics.*') which reads env vars when
 * the operator hasn't populated a setting yet.
 *
 * Values are cached per-request (cheap) — PlatformSetting itself
 * caches across requests via Cache::remember.
 */
class AnalyticsConfig
{
    private array $cache = [];

    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        // Map config dot-paths to PlatformSetting keys.
        $settingKeyMap = [
            'gtm_container_id'       => 'gtm_container_id',
            'ga4_measurement_id'     => 'ga4_measurement_id',
            'ga4_api_secret'         => 'ga4_api_secret',
            'google_ads_id'          => 'google_ads_conversion_id',
            'meta_pixel_id'          => 'meta_pixel_id',
            'meta_capi_access_token' => 'meta_capi_access_token',
            'meta_test_event_code'   => 'meta_test_event_code',
            'sgtm_url'               => 'sgtm_url',
            'enabled'                => 'analytics_enabled',
            'consent_enabled'        => 'cookie_consent_enabled',
        ];

        if (isset($settingKeyMap[$key])) {
            $fromDb = PlatformSetting::get($settingKeyMap[$key]);
            if ($fromDb !== null && $fromDb !== '') {
                return $this->cache[$key] = $fromDb;
            }
        }

        return $this->cache[$key] = config("analytics.{$key}", $default);
    }

    public function isEnabled(): bool
    {
        return (bool) $this->get('enabled', true) && $this->gtmId() !== null;
    }

    public function gtmId(): ?string
    {
        $id = $this->get('gtm_container_id');
        return $id ? trim($id) : null;
    }

    public function ga4Id(): ?string
    {
        $id = $this->get('ga4_measurement_id');
        return $id ? trim($id) : null;
    }

    public function metaPixelId(): ?string
    {
        $id = $this->get('meta_pixel_id');
        return $id ? trim($id) : null;
    }

    public function googleAdsId(): ?string
    {
        $id = $this->get('google_ads_id');
        return $id ? trim($id) : null;
    }

    public function sgtmUrl(): ?string
    {
        $url = $this->get('sgtm_url');
        return $url ? rtrim(trim($url), '/') : null;
    }

    public function consentDefaults(): array
    {
        return config('analytics.consent_defaults', []);
    }

    public function consentCategories(): array
    {
        return config('analytics.consent_categories', []);
    }
}
