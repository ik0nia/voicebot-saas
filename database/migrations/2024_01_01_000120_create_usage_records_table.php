<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('type');
            $table->decimal('quantity', 10, 2);
            $table->unsignedInteger('unit_cost_cents')->default(0);
            $table->timestamp('recorded_at')->nullable();
            $table->date('period_start');
            $table->date('period_end');
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('type');
            $table->index('period_start');
            $table->index('period_end');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_records');
    }
};
