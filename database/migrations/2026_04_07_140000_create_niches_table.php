<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('niches', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique()->index();
            $table->string('name');
            $table->string('vertical_label');
            $table->string('color_theme');
            $table->string('hero_eyebrow')->nullable();
            $table->string('hero_title');
            $table->text('hero_subtitle');
            $table->string('problem_title');
            $table->text('problem_text');
            $table->string('solution_title');
            $table->text('solution_text');
            $table->json('benefits');
            $table->json('demo_messages');
            $table->text('social_proof_text')->nullable();
            $table->string('cta_primary_text')->default('Începe gratuit');
            $table->string('cta_primary_href')->default('/register');
            $table->string('cta_secondary_text')->default('Vorbește cu noi');
            $table->string('cta_secondary_href')->default('/contact');
            $table->string('image_path')->nullable();
            $table->text('icon_svg')->nullable();
            $table->json('faq');
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 160)->nullable();
            $table->integer('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('niches');
    }
};
