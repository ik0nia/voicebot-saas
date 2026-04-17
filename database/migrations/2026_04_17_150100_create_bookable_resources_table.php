<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unified bookable resource abstraction covering doctors,
 * staff, rooms, tables, service bays, and future "generic"
 * kinds. Additive to staff_members + resource_inventory —
 * those tables continue to work with the original actions
 * while new code migrates to this model.
 *
 * The kind column lets the availability action decide between
 * per-minute slot math (services) and date-range math
 * (hospitality). external_calendar_* columns are placeholders
 * for future sync; nothing reads them yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookable_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();

            // Matches the business model the resource represents.
            // room / table lanes share the id space with the
            // legacy resource_inventory table so future migration
            // can backfill without a schema change here.
            $table->enum('kind', ['doctor', 'staff', 'room', 'table', 'bay', 'generic'])->default('staff');

            $table->string('name', 180);
            $table->string('role', 120)->nullable();           // "Medic cardiolog", "Coafor senior"
            $table->text('bio')->nullable();
            $table->string('avatar_url', 500)->nullable();

            // Capacity — relevant for room/table; 1 for a solo
            // doctor. Keep nullable for data that doesn't have
            // a meaningful capacity notion.
            $table->unsignedSmallInteger('capacity')->nullable();

            // Services this resource delivers. Null = handles
            // anything the bot offers (matches staff_members
            // convention for solo operators).
            $table->json('service_type_ids')->nullable();

            // Optional links back to legacy rows so the bridge
            // layer can tell when a bookable_resource was
            // created as a shim around existing data.
            $table->foreignId('legacy_staff_member_id')->nullable()->constrained('staff_members')->nullOnDelete();
            $table->foreignId('legacy_resource_inventory_id')->nullable()->constrained('resource_inventory')->nullOnDelete();

            // Future Google Calendar / Outlook sync — placeholders.
            // Nothing reads them yet; future iterations honour them.
            $table->string('external_calendar_provider', 20)->nullable();  // 'google' | 'outlook'
            $table->string('external_calendar_id', 200)->nullable();
            $table->text('external_refresh_token')->nullable();
            $table->timestamp('external_last_synced_at')->nullable();

            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['bot_id', 'kind', 'is_active']);
            $table->index('department_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookable_resources');
    }
};
