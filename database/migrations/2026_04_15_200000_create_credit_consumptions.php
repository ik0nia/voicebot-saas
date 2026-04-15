<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('unit', 20); // messages | minutes | products
            $table->unsignedInteger('quantity');
            $table->string('source', 40); // chat_message | voice_call | product_sync | reconciliation
            $table->string('reference_id', 100)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'unit', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_consumptions');
    }
};
