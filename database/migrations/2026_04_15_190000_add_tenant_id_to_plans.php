<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Null = global plan (visible to everyone on /preturi).
            // Set = custom plan, visible only to that tenant; never public.
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->boolean('is_public')->default(true)->after('is_active');
            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['tenant_id', 'is_active']);
            $table->dropColumn(['tenant_id', 'is_public']);
        });
    }
};
