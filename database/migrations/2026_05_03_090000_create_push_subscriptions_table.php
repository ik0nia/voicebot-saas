<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Web Push subscriptions per user — pentru operator console PWA.
 *
 * Fiecare browser/device poate avea propria subscription. Storăm endpoint-ul
 * push service-ului (FCM/Mozilla/Apple) + cheile p256dh + auth ca să putem
 * trimite payloads criptate.
 *
 * Cleanup automatic la 410 Gone response (se rulează în PushService::send).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Endpoint URL pe push service-ul browser-ului — unique per device
            $table->text('endpoint');
            $table->string('endpoint_hash', 64)->unique(); // sha256 pentru a permite UNIQUE pe text lung

            // Keys necesare pentru a cripta payload-ul (Web Push protocol)
            $table->string('p256dh', 256);
            $table->string('auth', 64);

            // Metadata pentru UX (operator să vadă „mobil iPhone" vs „desktop Chrome")
            $table->string('user_agent', 500)->nullable();
            $table->string('label', 100)->nullable();

            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
