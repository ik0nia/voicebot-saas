<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('model_pricing', function (Blueprint $table) {
            $table->id();
            $table->string('model_id')->unique();
            $table->string('provider');
            $table->decimal('input_cost_per_million', 10, 4);
            $table->decimal('output_cost_per_million', 10, 4);
            $table->integer('max_context_tokens')->default(128000);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed default pricing
        DB::table('model_pricing')->insert([
            ['model_id' => 'gpt-4o-mini', 'provider' => 'openai', 'input_cost_per_million' => 0.15, 'output_cost_per_million' => 0.60, 'max_context_tokens' => 128000, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['model_id' => 'gpt-4o', 'provider' => 'openai', 'input_cost_per_million' => 2.50, 'output_cost_per_million' => 10.00, 'max_context_tokens' => 128000, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['model_id' => 'claude-haiku-4-5-20251001', 'provider' => 'anthropic', 'input_cost_per_million' => 0.80, 'output_cost_per_million' => 4.00, 'max_context_tokens' => 200000, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['model_id' => 'claude-sonnet-4-5-20241022', 'provider' => 'anthropic', 'input_cost_per_million' => 3.00, 'output_cost_per_million' => 15.00, 'max_context_tokens' => 200000, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::create('ai_api_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('model');
            $table->integer('input_tokens')->default(0);
            $table->integer('output_tokens')->default(0);
            $table->integer('cost_cents')->default(0);
            $table->integer('response_time_ms')->default(0);
            $table->string('status', 20)->default('success'); // success, error, timeout, rate_limit
            $table->string('error_type')->nullable();
            $table->unsignedBigInteger('bot_id')->nullable();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->timestamps();

            $table->index(['provider', 'created_at']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_api_metrics');
        Schema::dropIfExists('model_pricing');
    }
};
