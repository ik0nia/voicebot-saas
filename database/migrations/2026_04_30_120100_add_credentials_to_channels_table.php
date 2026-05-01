<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            // Encrypted JSON blob for per-channel credentials.
            // Cast as 'encrypted:array' on the model — Laravel encrypts the
            // whole array via Crypt (APP_KEY) before persisting and decrypts
            // on read. Stored as text because Postgres jsonb cannot hold
            // ciphertext directly.
            $table->text('credentials')->nullable()->after('config');
        });
    }

    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropColumn('credentials');
        });
    }
};
