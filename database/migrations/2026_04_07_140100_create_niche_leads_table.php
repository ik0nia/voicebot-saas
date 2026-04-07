<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('niche_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('niche_id')->constrained('niches')->cascadeOnDelete();
            $table->string('name');
            $table->string('business_name')->nullable();
            $table->string('email');
            $table->string('phone');
            $table->text('message')->nullable();
            $table->string('source_url')->nullable();
            $table->string('ip')->nullable();
            $table->timestamps();

            $table->index('niche_id');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('niche_leads');
    }
};
