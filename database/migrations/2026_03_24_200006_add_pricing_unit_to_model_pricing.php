<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('model_pricing', function (Blueprint $table) {
            $table->string('pricing_unit', 20)->default('1M_tokens')->after('provider');
            // pricing_unit: '1M_tokens', 'minute', '1K_chars'
        });

        // Rename columns to be unit-agnostic
        Schema::table('model_pricing', function (Blueprint $table) {
            $table->renameColumn('input_cost_per_million', 'input_cost');
            $table->renameColumn('output_cost_per_million', 'output_cost');
        });

        // Update existing models with correct units and prices
        DB::table('model_pricing')->where('model_id', 'gpt-4o-realtime-preview')->update([
            'pricing_unit' => '1M_tokens',
            'input_cost' => 5.00,
            'output_cost' => 20.00,
        ]);
        DB::table('model_pricing')->where('model_id', 'gpt-4o-realtime-preview-audio')->update([
            'pricing_unit' => 'minute',
            'input_cost' => 0.06,   // $0.06/min audio input
            'output_cost' => 0.24,  // $0.24/min audio output
        ]);
        DB::table('model_pricing')->where('model_id', 'whisper-1')->update([
            'pricing_unit' => 'minute',
            'input_cost' => 0.006,  // $0.006/min
            'output_cost' => 0,
        ]);
        DB::table('model_pricing')->where('model_id', 'text-embedding-3-small')->update([
            'pricing_unit' => '1M_tokens',
            'input_cost' => 0.02,
            'output_cost' => 0,
        ]);
        DB::table('model_pricing')->where('model_id', 'text-embedding-3-large')->update([
            'pricing_unit' => '1M_tokens',
            'input_cost' => 0.13,
            'output_cost' => 0,
        ]);
        DB::table('model_pricing')->where('model_id', 'eleven_multilingual_v2')->update([
            'pricing_unit' => '1K_chars',
            'input_cost' => 0.30,   // ~$0.30/1K characters
            'output_cost' => 0,
        ]);
        DB::table('model_pricing')->where('model_id', 'eleven_turbo_v2_5')->update([
            'pricing_unit' => '1K_chars',
            'input_cost' => 0.15,
            'output_cost' => 0,
        ]);
    }

    public function down(): void
    {
        Schema::table('model_pricing', function (Blueprint $table) {
            $table->renameColumn('input_cost', 'input_cost_per_million');
            $table->renameColumn('output_cost', 'output_cost_per_million');
            $table->dropColumn('pricing_unit');
        });
    }
};
