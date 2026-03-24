<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('ai_model')->nullable()->after('metadata');
            $table->string('ai_provider')->nullable()->after('ai_model');
            $table->unsignedInteger('input_tokens')->default(0)->after('ai_provider');
            $table->unsignedInteger('output_tokens')->default(0)->after('input_tokens');
            $table->decimal('cost_cents', 8, 4)->default(0)->after('output_tokens');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['ai_model', 'ai_provider', 'input_tokens', 'output_tokens', 'cost_cents']);
        });
    }
};
