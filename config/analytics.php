<?php

/**
 * Marketing & analytics configuration. Values resolve from
 * PlatformSetting at runtime via app('analytics.config'), so
 * the operator can change them in Admin → Setări → Marketing
 * without a redeploy. This file only carries defaults +
 * static policy (category defaults for Consent Mode v2).
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Container + property IDs (resolved at runtime from PlatformSetting)
    |--------------------------------------------------------------------------
    */
    'gtm_container_id'       => env('GTM_CONTAINER_ID'),       // e.g. GTM-XXXXXXX
    'ga4_measurement_id'     => env('GA4_MEASUREMENT_ID'),     // e.g. G-XXXXXXXXXX
    'ga4_api_secret'         => env('GA4_API_SECRET'),         // for Measurement Protocol
    'google_ads_id'          => env('GOOGLE_ADS_CONVERSION_ID'), // e.g. AW-123456789
    'google_ads_labels'      => [
        'sign_up'   => env('GOOGLE_ADS_LABEL_SIGN_UP'),
        'lead'      => env('GOOGLE_ADS_LABEL_LEAD'),
        'purchase'  => env('GOOGLE_ADS_LABEL_PURCHASE'),
    ],
    'meta_pixel_id'          => env('META_PIXEL_ID'),
    'meta_capi_access_token' => env('META_CAPI_ACCESS_TOKEN'),
    'meta_test_event_code'   => env('META_CAPI_TEST_CODE'), // optional, for CAPI diagnostics

    /*
    |--------------------------------------------------------------------------
    | Server-Side Tagging
    |--------------------------------------------------------------------------
    | When sgtm_url is set, GA4 + Meta Pixel tags route their hits
    | through our own sGTM container (e.g. tags.sambla.ro) so
    | client-side ad-blockers can't silently drop conversions.
    */
    'sgtm_url'               => env('SGTM_URL'), // e.g. https://tags.sambla.ro

    /*
    |--------------------------------------------------------------------------
    | Feature toggles
    |--------------------------------------------------------------------------
    */
    'enabled'          => env('ANALYTICS_ENABLED', true),
    'consent_enabled'  => env('COOKIE_CONSENT_ENABLED', true),
    'consent_version'  => '1.0', // bump to re-prompt everyone after a policy change

    /*
    |--------------------------------------------------------------------------
    | Consent Mode v2 defaults
    |--------------------------------------------------------------------------
    | These fire BEFORE the user sees the banner. Must match what
    | the cookie widget will default to. ads_data_redaction +
    | url_passthrough come from Google's recommended policy.
    */
    'consent_defaults' => [
        'analytics_storage'       => 'denied',
        'ad_storage'              => 'denied',
        'ad_user_data'            => 'denied',
        'ad_personalization'      => 'denied',
        'functionality_storage'   => 'granted',
        'security_storage'        => 'granted',
        'personalization_storage' => 'denied',
    ],

    /*
    |--------------------------------------------------------------------------
    | Consent category → Consent Mode v2 signals
    |--------------------------------------------------------------------------
    | The UI shows 4 user-facing categories; each maps to one or
    | more Consent Mode v2 signals. Keep the category labels short
    | and in the user's language — text lives in the Blade partial.
    */
    'consent_categories' => [
        'necessary'    => ['security_storage', 'functionality_storage'],
        'analytics'    => ['analytics_storage'],
        'marketing'    => ['ad_storage', 'ad_user_data', 'ad_personalization'],
        'personalization' => ['personalization_storage'],
    ],

    /*
    |--------------------------------------------------------------------------
    | UTM / click-ID params we recognise
    |--------------------------------------------------------------------------
    */
    'utm_params' => [
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
        'gclid', 'fbclid', 'msclkid', 'ttclid', 'li_fat_id',
    ],
];
