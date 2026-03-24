<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->decimal('sentiment_score', 4, 3)->nullable()->after('cost_cents');
            $table->string('sentiment_label', 20)->nullable()->after('sentiment_score');

            $table->index('sentiment_label');
        });
    }

    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropIndex(['sentiment_label']);
            $table->dropColumn(['sentiment_score', 'sentiment_label']);
        });
    }
};
