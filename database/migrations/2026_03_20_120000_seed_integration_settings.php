<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            // WhatsApp
            ['key' => 'whatsapp_provider', 'value' => 'meta_cloud_api', 'type' => 'string', 'group' => 'whatsapp'],
            ['key' => 'whatsapp_api_key', 'value' => '', 'type' => 'string', 'group' => 'whatsapp'],
            ['key' => 'whatsapp_phone_number_id', 'value' => '', 'type' => 'string', 'group' => 'whatsapp'],
            ['key' => 'whatsapp_business_account_id', 'value' => '', 'type' => 'string', 'group' => 'whatsapp'],
            ['key' => 'whatsapp_verify_token', 'value' => '', 'type' => 'string', 'group' => 'whatsapp'],
            ['key' => 'whatsapp_webhook_url', 'value' => 'https://sambla.ro/webhook/whatsapp', 'type' => 'string', 'group' => 'whatsapp'],

            // Facebook
            ['key' => 'facebook_app_id', 'value' => '', 'type' => 'string', 'group' => 'facebook'],
            ['key' => 'facebook_app_secret', 'value' => '', 'type' => 'string', 'group' => 'facebook'],
            ['key' => 'facebook_page_access_token', 'value' => '', 'type' => 'string', 'group' => 'facebook'],
            ['key' => 'facebook_verify_token', 'value' => '', 'type' => 'string', 'group' => 'facebook'],
            ['key' => 'facebook_webhook_url', 'value' => 'https://sambla.ro/webhook/facebook', 'type' => 'string', 'group' => 'facebook'],

            // Instagram
            ['key' => 'instagram_app_id', 'value' => '', 'type' => 'string', 'group' => 'instagram'],
            ['key' => 'instagram_app_secret', 'value' => '', 'type' => 'string', 'group' => 'instagram'],
            ['key' => 'instagram_access_token', 'value' => '', 'type' => 'string', 'group' => 'instagram'],
            ['key' => 'instagram_verify_token', 'value' => '', 'type' => 'string', 'group' => 'instagram'],
            ['key' => 'instagram_webhook_url', 'value' => 'https://sambla.ro/webhook/instagram', 'type' => 'string', 'group' => 'instagram'],
        ];

        foreach ($defaults as $setting) {
            $setting['created_at'] = now();
            $setting['updated_at'] = now();
            DB::table('platform_settings')->insertOrIgnore($setting);
        }
    }

    public function down(): void
    {
        DB::table('platform_settings')->whereIn('group', ['whatsapp', 'facebook', 'instagram'])->delete();
    }
};
