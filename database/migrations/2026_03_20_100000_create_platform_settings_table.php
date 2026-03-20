<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string'); // string, boolean, integer, float, json
            $table->string('group', 50)->default('general');
            $table->timestamps();

            $table->index('group');
        });

        // Seed default settings
        $defaults = [
            // General
            ['key' => 'platform_name', 'value' => 'Sambla', 'type' => 'string', 'group' => 'general'],
            ['key' => 'platform_url', 'value' => 'https://sambla.ro', 'type' => 'string', 'group' => 'general'],
            ['key' => 'support_email', 'value' => 'support@sambla.ro', 'type' => 'string', 'group' => 'general'],
            ['key' => 'maintenance_mode', 'value' => '0', 'type' => 'boolean', 'group' => 'general'],
            ['key' => 'registration_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'general'],
            ['key' => 'default_timezone', 'value' => 'Europe/Bucharest', 'type' => 'string', 'group' => 'general'],
            ['key' => 'default_language', 'value' => 'ro', 'type' => 'string', 'group' => 'general'],

            // OpenAI
            ['key' => 'openai_api_key', 'value' => '', 'type' => 'string', 'group' => 'openai'],
            ['key' => 'openai_organization', 'value' => '', 'type' => 'string', 'group' => 'openai'],
            ['key' => 'openai_realtime_model', 'value' => 'gpt-4o-realtime-preview', 'type' => 'string', 'group' => 'openai'],
            ['key' => 'openai_max_tokens', 'value' => '4096', 'type' => 'integer', 'group' => 'openai'],
            ['key' => 'openai_temperature', 'value' => '0.7', 'type' => 'float', 'group' => 'openai'],

            // Twilio
            ['key' => 'twilio_sid', 'value' => '', 'type' => 'string', 'group' => 'twilio'],
            ['key' => 'twilio_auth_token', 'value' => '', 'type' => 'string', 'group' => 'twilio'],
            ['key' => 'twilio_phone_number', 'value' => '', 'type' => 'string', 'group' => 'twilio'],
            ['key' => 'twilio_webhook_url', 'value' => 'https://sambla.ro/webhook/twilio/voice', 'type' => 'string', 'group' => 'twilio'],

            // Stripe
            ['key' => 'stripe_public_key', 'value' => '', 'type' => 'string', 'group' => 'stripe'],
            ['key' => 'stripe_secret_key', 'value' => '', 'type' => 'string', 'group' => 'stripe'],
            ['key' => 'stripe_webhook_secret', 'value' => '', 'type' => 'string', 'group' => 'stripe'],
            ['key' => 'stripe_currency', 'value' => 'eur', 'type' => 'string', 'group' => 'stripe'],

            // Email
            ['key' => 'mail_mailer', 'value' => 'smtp', 'type' => 'string', 'group' => 'email'],
            ['key' => 'mail_host', 'value' => '', 'type' => 'string', 'group' => 'email'],
            ['key' => 'mail_port', 'value' => '587', 'type' => 'integer', 'group' => 'email'],
            ['key' => 'mail_username', 'value' => '', 'type' => 'string', 'group' => 'email'],
            ['key' => 'mail_password', 'value' => '', 'type' => 'string', 'group' => 'email'],
            ['key' => 'mail_encryption', 'value' => 'tls', 'type' => 'string', 'group' => 'email'],
            ['key' => 'mail_from_address', 'value' => 'noreply@sambla.ro', 'type' => 'string', 'group' => 'email'],
            ['key' => 'mail_from_name', 'value' => 'Sambla', 'type' => 'string', 'group' => 'email'],

            // Security
            ['key' => 'bcrypt_rounds', 'value' => '12', 'type' => 'integer', 'group' => 'security'],
            ['key' => 'session_lifetime', 'value' => '120', 'type' => 'integer', 'group' => 'security'],
            ['key' => 'api_rate_limit', 'value' => '60', 'type' => 'integer', 'group' => 'security'],
            ['key' => 'max_login_attempts', 'value' => '5', 'type' => 'integer', 'group' => 'security'],
            ['key' => 'password_min_length', 'value' => '8', 'type' => 'integer', 'group' => 'security'],
        ];

        foreach ($defaults as $setting) {
            $setting['created_at'] = now();
            $setting['updated_at'] = now();
            \DB::table('platform_settings')->insert($setting);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};
