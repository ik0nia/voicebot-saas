<?php

use App\Models\PlatformSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = [
            ['key' => 'stripe_mode', 'value' => 'live', 'type' => 'string', 'group' => 'stripe'],
            ['key' => 'stripe_test_public_key', 'value' => '', 'type' => 'string', 'group' => 'stripe'],
            ['key' => 'stripe_test_secret_key', 'value' => '', 'type' => 'string', 'group' => 'stripe'],
            ['key' => 'stripe_test_webhook_secret', 'value' => '', 'type' => 'string', 'group' => 'stripe'],
        ];

        foreach ($rows as $row) {
            $exists = DB::table('platform_settings')->where('key', $row['key'])->exists();
            if ($exists) {
                continue;
            }
            DB::table('platform_settings')->insert(array_merge($row, [
                'is_encrypted' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        \Cache::forget('platform_settings');
    }

    public function down(): void
    {
        DB::table('platform_settings')->whereIn('key', [
            'stripe_mode',
            'stripe_test_public_key',
            'stripe_test_secret_key',
            'stripe_test_webhook_secret',
        ])->delete();

        \Cache::forget('platform_settings');
    }
};
