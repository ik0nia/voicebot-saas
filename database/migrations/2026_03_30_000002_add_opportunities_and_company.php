<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Opportunities on conversations
        Schema::table('conversations', function (Blueprint $table) {
            $table->smallInteger('opportunity_score')->default(0)->after('lead_score');
            $table->boolean('is_opportunity')->default(false)->after('opportunity_score');
            $table->jsonb('opportunity_reasons')->nullable()->after('is_opportunity');
        });

        // Company/billing fields on tenants
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('company_name', 255)->nullable();
            $table->string('company_cif', 50)->nullable();
            $table->string('company_reg_number', 50)->nullable();
            $table->text('company_address')->nullable();
            $table->string('company_city', 100)->nullable();
            $table->string('company_county', 100)->nullable();
            $table->string('company_country', 100)->default('România');
            $table->string('company_zip', 20)->nullable();
            $table->string('company_email', 255)->nullable();
            $table->string('company_phone', 50)->nullable();
            $table->string('company_contact_person', 255)->nullable();
            $table->string('company_bank', 255)->nullable();
            $table->string('company_iban', 50)->nullable();
            $table->boolean('billing_complete')->default(false);
        });

        // Internal notes on leads
        if (!Schema::hasColumn('leads', 'internal_notes')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->text('internal_notes')->nullable()->after('notes');
            });
        }

        // Indexes
        DB::statement("CREATE INDEX IF NOT EXISTS idx_conv_opportunity ON conversations (tenant_id, is_opportunity, created_at) WHERE is_opportunity = TRUE");
    }

    public function down(): void
    {
        DB::statement("DROP INDEX IF EXISTS idx_conv_opportunity");

        Schema::table('leads', function (Blueprint $t) {
            if (Schema::hasColumn('leads', 'internal_notes')) $t->dropColumn('internal_notes');
        });

        Schema::table('tenants', function (Blueprint $t) {
            $t->dropColumn([
                'company_name', 'company_cif', 'company_reg_number', 'company_address',
                'company_city', 'company_county', 'company_country', 'company_zip',
                'company_email', 'company_phone', 'company_contact_person',
                'company_bank', 'company_iban', 'billing_complete',
            ]);
        });

        Schema::table('conversations', function (Blueprint $t) {
            $t->dropColumn(['opportunity_score', 'is_opportunity', 'opportunity_reasons']);
        });
    }
};
