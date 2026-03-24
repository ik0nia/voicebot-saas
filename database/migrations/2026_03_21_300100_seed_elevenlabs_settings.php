<?php

use App\Models\PlatformSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            ['key' => 'elevenlabs_api_key', 'value' => '', 'type' => 'string', 'group' => 'elevenlabs'],
            ['key' => 'elevenlabs_model_id', 'value' => 'eleven_multilingual_v2', 'type' => 'string', 'group' => 'elevenlabs'],
            ['key' => 'elevenlabs_stability', 'value' => '0.5', 'type' => 'float', 'group' => 'elevenlabs'],
            ['key' => 'elevenlabs_similarity_boost', 'value' => '0.75', 'type' => 'float', 'group' => 'elevenlabs'],
        ];

        foreach ($settings as $setting) {
            PlatformSetting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }

    public function down(): void
    {
        PlatformSetting::where('group', 'elevenlabs')->delete();
    }
};
