<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seeds empty structured-prompt defaults into bots.settings (JSON column).
 *
 * No schema change — the existing settings JSON column holds the new keys.
 * Runtime code reads these via the PromptBuilder service, gated by the
 * `use_structured_prompt` feature flag (default false). Existing bots keep
 * using their freeform system_prompt until an operator flips the flag.
 *
 * Idempotent: only initialises keys that don't already exist in settings,
 * so re-running is safe and preserves any values an admin already set.
 *
 * DOWN is a no-op — dropping data from tenant bots just to reverse a
 * migration would be destructive and these keys are harmless if left in.
 */
return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            'use_structured_prompt' => false,
            'business_info' => [
                'address' => '',
                'hours_text' => '',
                'hours_schedule' => [],
                'phone' => '',
                'email' => '',
                'website' => '',
                'whatsapp' => '',
                'facebook' => '',
                'instagram' => '',
                'extras' => '',
            ],
            'faqs' => [],
            'dont_rules' => [],
            'tone_guide' => [
                'length' => 'medium',
                'register' => 'tu',
                'emoji_ok' => false,
                'languages' => ['ro'],
            ],
        ];

        // Raw DB access so we bypass the BelongsToTenant global scope.
        DB::table('bots')->orderBy('id')->chunkById(200, function ($bots) use ($defaults) {
            foreach ($bots as $row) {
                $settings = [];
                if (!empty($row->settings)) {
                    $decoded = json_decode($row->settings, true);
                    if (is_array($decoded)) {
                        $settings = $decoded;
                    }
                }

                $changed = false;
                foreach ($defaults as $key => $value) {
                    if (!array_key_exists($key, $settings)) {
                        $settings[$key] = $value;
                        $changed = true;
                    }
                }

                if ($changed) {
                    DB::table('bots')
                        ->where('id', $row->id)
                        ->update(['settings' => json_encode($settings)]);
                }
            }
        });
    }

    public function down(): void
    {
        // No-op — keys are inert when use_structured_prompt = false.
    }
};
