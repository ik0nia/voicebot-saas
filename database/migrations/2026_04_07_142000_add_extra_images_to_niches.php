<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('niches', function (Blueprint $table) {
            $table->string('image_hero', 255)->nullable()->after('image_path');
            $table->string('image_accent', 255)->nullable()->after('image_hero');
        });
    }

    public function down(): void
    {
        Schema::table('niches', function (Blueprint $table) {
            $table->dropColumn(['image_hero', 'image_accent']);
        });
    }
};
