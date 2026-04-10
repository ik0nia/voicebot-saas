<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_oauth_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Identity from Google
            $table->string('google_user_id')->nullable();
            $table->string('google_email')->nullable();

            // Tokens — encrypted at rest via Eloquent cast
            $table->text('access_token');           // encrypted (~1h lifetime)
            $table->text('refresh_token')->nullable(); // encrypted (long-lived; may be null on re-consent without prompt=consent)
            $table->timestamp('expires_at')->nullable();

            // Granted scopes (space-separated string per Google convention)
            $table->text('scopes')->nullable();

            // Convention folder created in user's Drive on first connect (if drive.file scope)
            $table->string('kb_folder_id')->nullable();

            $table->timestamps();

            // One active connection per tenant (per-tenant model, not per-user)
            $table->unique('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_oauth_tokens');
    }
};
