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

    'telnyx' => [
        'api_key' => env('TELNYX_API_KEY'),
        'connection_id' => env('TELNYX_CONNECTION_ID'),
        'public_key' => env('TELNYX_PUBLIC_KEY'),
    ],

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'organization' => env('OPENAI_ORGANIZATION'),
        'realtime_model' => env('OPENAI_REALTIME_MODEL', 'gpt-4o-realtime-preview'),
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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'picker_api_key' => env('GOOGLE_PICKER_API_KEY'),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI', env('APP_URL') . '/oauth/google/callback'),
        // Drive folder name created on first connect (convention, not auto-watched)
        'kb_folder_name' => env('GOOGLE_KB_FOLDER_NAME', 'Sambla Knowledge Base'),
    ],

    // Meta (Facebook/WhatsApp/Instagram) webhook signing secret. Must exist
    // in config/ so `php artisan config:cache` captures it — otherwise
    // callers resort to env() which returns null after caching and the
    // webhook middleware loses its fail-closed guarantee.
    'meta' => [
        'app_secret' => env('META_APP_SECRET'),
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

];
