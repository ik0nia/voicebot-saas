<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SENSITIVE_PATTERNS = [
        '_secret_key',
        '_api_key',
        '_webhook_secret',
        '_password',
        '_secret',
        '_token',
    ];

    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->boolean('is_encrypted')->default(false)->after('group');
        });

        DB::table('platform_settings')->orderBy('id')->each(function ($row) {
            if (! $this->isSensitive($row->key)) {
                return;
            }
            $value = $row->value;
            if ($value === null || $value === '') {
                return;
            }
            if ($this->looksEncrypted($value)) {
                DB::table('platform_settings')->where('id', $row->id)->update(['is_encrypted' => true]);
                return;
            }
            DB::table('platform_settings')->where('id', $row->id)->update([
                'value' => Crypt::encryptString($value),
                'is_encrypted' => true,
            ]);
        });
    }

    public function down(): void
    {
        DB::table('platform_settings')->where('is_encrypted', true)->orderBy('id')->each(function ($row) {
            try {
                DB::table('platform_settings')->where('id', $row->id)->update([
                    'value' => Crypt::decryptString($row->value),
                    'is_encrypted' => false,
                ]);
            } catch (\Throwable $e) {
                // leave as-is if cannot decrypt
            }
        });

        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn('is_encrypted');
        });
    }

    private function isSensitive(string $key): bool
    {
        foreach (self::SENSITIVE_PATTERNS as $needle) {
            if (str_ends_with($key, $needle)) {
                return true;
            }
        }
        return false;
    }

    private function looksEncrypted(string $value): bool
    {
        if (! str_starts_with($value, 'eyJ')) {
            return false;
        }
        try {
            Crypt::decryptString($value);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
};
