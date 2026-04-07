<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('niches', function (Blueprint $table) {
            // formal | professional | friendly | playful — drives emoji density
            // and writing-style decisions in the landing template.
            $table->string('tone', 32)->default('professional')->after('color_theme');
        });
    }

    public function down(): void
    {
        Schema::table('niches', function (Blueprint $table) {
            $table->dropColumn('tone');
        });
    }
};
