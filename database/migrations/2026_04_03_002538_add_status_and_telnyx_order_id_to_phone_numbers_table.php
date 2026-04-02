<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('phone_numbers', function (Blueprint $table) {
            $table->string('status')->default('active')->after('is_active');
            $table->string('telnyx_order_id')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('phone_numbers', function (Blueprint $table) {
            $table->dropColumn(['status', 'telnyx_order_id']);
        });
    }
};
