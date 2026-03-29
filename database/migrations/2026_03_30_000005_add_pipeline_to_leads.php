<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Pipeline stage
            $table->string('pipeline_stage', 30)->default('new')->after('status');

            // Scheduling (callback)
            $table->string('service_type', 100)->nullable()->after('project_type');
            $table->date('preferred_date')->nullable()->after('service_type');
            $table->string('preferred_time_slot', 30)->nullable()->after('preferred_date');

            // Assignment & outcome
            $table->string('assigned_to', 255)->nullable()->after('capture_reason');
            $table->string('outcome', 100)->nullable()->after('assigned_to');
            $table->decimal('estimated_value', 10, 2)->nullable()->after('outcome');

            // Timestamps for pipeline progression
            $table->timestamp('contacted_at')->nullable()->after('sent_to_crm_at');
            $table->timestamp('scheduled_at')->nullable()->after('contacted_at');
            $table->timestamp('met_at')->nullable()->after('scheduled_at');
            $table->timestamp('quoted_at')->nullable()->after('met_at');
            $table->timestamp('won_at')->nullable()->after('quoted_at');
            $table->timestamp('lost_at')->nullable()->after('won_at');
            $table->string('lost_reason', 255)->nullable()->after('lost_at');

            // Source page (from callback widget)
            $table->string('source_page_url', 500)->nullable()->after('capture_reason');

            $table->index(['tenant_id', 'pipeline_stage']);
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'pipeline_stage']);
            $table->dropColumn([
                'pipeline_stage', 'service_type', 'preferred_date', 'preferred_time_slot',
                'assigned_to', 'outcome', 'estimated_value',
                'contacted_at', 'scheduled_at', 'met_at', 'quoted_at', 'won_at', 'lost_at', 'lost_reason',
                'source_page_url',
            ]);
        });
    }
};
