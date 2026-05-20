<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'twiml_app_sid' => env('TWILIO_TWIML_APP_SID'),
        // Regulatory Bundle + Address — required to purchase numbers in
        // regulated countries (RO, DE, FR, GB, …). Submitted and
        // approved once at the platform level; subaccounts share it per
        // Twilio's "subaccount regulatory inheritance" feature.
        'bundle_sid_ro' => env('TWILIO_RO_BUNDLE_SID'),
        'address_sid_ro' => env('TWILIO_RO_ADDRESS_SID'),
    ],

    'internal' => [
        // Shared secret for in-platform service-to-service calls
        // (currently: media-stream → Laravel for transcript + usage).
        // Env-only on purpose — keeps the token out of the DB.
        'service_token' => env('INTERNAL_SERVICE_TOKEN'),
    ],

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'organization' => env('OPENAI_ORGANIZATION'),
        'realtime_model' => env('OPENAI_REALTIME_MODEL', 'gpt-realtime-2'),
        'realtime_reasoning_effort' => env('OPENAI_REASONING_EFFORT', 'low'),
    ],

    'elevenlabs' => [
        'api_key' => env('ELEVENLABS_API_KEY'),
        'model_id' => env('ELEVENLABS_MODEL_ID', 'eleven_multilingual_v2'),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash-preview-05-20'),
        'image_model' => env('GEMINI_IMAGE_MODEL', 'gemini-3.1-flash'),
    ],

    'image_generation' => [
        // Primary backend for social-media images. Either 'gpt-image-2' (OpenAI) or 'vertex' (legacy Gemini 3 on Vertex AI).
        'provider' => env('IMAGE_GENERATION_PROVIDER', 'gpt-image-2'),
        'gpt_image_2_model' => env('GPT_IMAGE_2_MODEL', 'gpt-image-2'),
        // Model used by RomanianPromptComposer to polish RO copy before it hits the image model.
        'composer_model' => env('RO_COMPOSER_MODEL', 'gpt-5.4'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'picker_api_key' => env('GOOGLE_PICKER_API_KEY'),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI', env('APP_URL') . '/oauth/google/callback'),
        // Drive folder name created on first connect (convention, not auto-watched)
        'kb_folder_name' => env('GOOGLE_KB_FOLDER_NAME', 'Sambla Knowledge Base'),
    ],

    // Meta (Facebook/WhatsApp/Instagram) — single tenant-facing app
    // identity. app_id/app_secret authenticate Sambla as the OAuth
    // client when tenants connect their FB pages or IG accounts; the
    // verify_token is the App-level webhook handshake string Meta
    // sends once at "Verify and Save" in App Dashboard. Must live in
    // config/ so `php artisan config:cache` captures these — direct
    // env() calls return null after caching and break OAuth + webhook.
    'meta' => [
        'app_id' => env('META_APP_ID'),
        'app_secret' => env('META_APP_SECRET'),
        'verify_token' => env('META_WEBHOOK_VERIFY_TOKEN'),
        // Graph API version we pin against. Bumping is a deliberate
        // op — newer versions occasionally remove fields we depend on
        // (e.g. ig_id removed in v18). Keep behind config so we can
        // canary one tenant before rolling forward.
        'graph_version' => env('META_GRAPH_VERSION', 'v21.0'),
        // Default scopes asked at OAuth time. Keep aligned with what
        // we requested at App Review — asking for an unapproved scope
        // returns an error to the user mid-flow.
        'scopes' => [
            'pages_messaging',
            'pages_manage_metadata',
            'pages_show_list',
            'pages_read_engagement',
            'pages_utility_messaging',
            'instagram_basic',
            'instagram_manage_messages',
            'business_management',
            'email',
            'public_profile',
        ],
    ],

    'whatsapp' => [
        'token' => env('WHATSAPP_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
    ],

    'facebook' => [
        'page_access_token' => env('FACEBOOK_PAGE_ACCESS_TOKEN'),
        'verify_token' => env('FACEBOOK_VERIFY_TOKEN'),
    ],

    'instagram' => [
        'page_access_token' => env('INSTAGRAM_PAGE_ACCESS_TOKEN'),
        'verify_token' => env('INSTAGRAM_VERIFY_TOKEN'),
    ],

    // Letta (formerly MemGPT) sidecar — opt-in. When LETTA_URL is set,
    // LettaBridgeService routes inbound messages through the sidecar
    // for cross-channel memory continuity. When unset, the orchestrator
    // skips Letta and uses direct LLM (no behavior change).
    // The sidecar is deployed as a separate container (see
    // docker-compose entry for `letta` — commented by default).
    'letta' => [
        'url' => env('LETTA_URL'),         // e.g. http://letta:8283
        'token' => env('LETTA_TOKEN'),     // bearer token configured on the sidecar
    ],

    // VAPID keys pentru Web Push (operator console PWA)
    'vapid' => [
        'subject' => env('VAPID_SUBJECT', 'mailto:noreply@sambla.ro'),
        'public'  => env('VAPID_PUBLIC_KEY'),
        'private' => env('VAPID_PRIVATE_KEY'),
    ],

];
