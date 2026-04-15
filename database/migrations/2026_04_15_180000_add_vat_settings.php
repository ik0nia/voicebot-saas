<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = [
            ['key' => 'vat_rate', 'value' => '21', 'type' => 'float', 'group' => 'tax'],
            ['key' => 'vat_country', 'value' => 'RO', 'type' => 'string', 'group' => 'tax'],
            ['key' => 'vat_inclusive', 'value' => '0', 'type' => 'boolean', 'group' => 'tax'],
            ['key' => 'collect_tax_id', 'value' => '1', 'type' => 'boolean', 'group' => 'tax'],
            // Stripe TaxRate IDs per mode, populated by stripe:setup-tax-rate.
            ['key' => 'stripe_tax_rate_id_live', 'value' => '', 'type' => 'string', 'group' => 'tax'],
            ['key' => 'stripe_tax_rate_id_test', 'value' => '', 'type' => 'string', 'group' => 'tax'],
        ];

        foreach ($rows as $row) {
            if (DB::table('platform_settings')->where('key', $row['key'])->exists()) {
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
            'vat_rate', 'vat_country', 'vat_inclusive', 'collect_tax_id',
            'stripe_tax_rate_id_live', 'stripe_tax_rate_id_test',
        ])->delete();
        \Cache::forget('platform_settings');
    }
};
