<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phone_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('bot_id')->nullable()->constrained('bots')->nullOnDelete();
            $table->string('number')->unique();
            $table->string('provider')->default('twilio');
            $table->string('friendly_name')->nullable();
            $table->unsignedInteger('monthly_cost_cents')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('bot_id');
            $table->index('number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_numbers');
    }
};
