<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('type'); // 'webchat', 'voice', 'bundle'
            $table->decimal('price_monthly', 10, 2);
            $table->decimal('price_yearly', 10, 2);
            $table->boolean('is_popular')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('limits')->nullable();
            $table->json('overage')->nullable();
            $table->json('features')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        $this->seedPlans();
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }

    private function seedPlans(): void
    {
        $now = now();

        DB::table('plans')->insert([
            // WEBCHAT PLANS
            [
                'slug' => 'chat-starter',
                'name' => 'Chat Starter',
                'type' => 'webchat',
                'price_monthly' => 29.00,
                'price_yearly' => 23.00,
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 1,
                'limits' => json_encode([
                    'bots' => 1,
                    'messages_per_month' => 1000,
                    'knowledge_entries' => 50,
                    'products' => 500,
                    'channels' => ['web_chatbot'],
                ]),
                'overage' => json_encode([
                    'cost_per_message' => 0.005,
                ]),
                'features' => json_encode([
                    '1 chatbot AI',
                    '1.000 mesaje/lună',
                    'Bază de cunoștințe (50 articole)',
                    'Widget personalizabil',
                    'Suport email',
                ]),
                'description' => 'Ideal pentru afaceri mici care vor un chatbot simplu.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'chat-professional',
                'name' => 'Chat Professional',
                'type' => 'webchat',
                'price_monthly' => 79.00,
                'price_yearly' => 63.00,
                'is_popular' => true,
                'is_active' => true,
                'sort_order' => 2,
                'limits' => json_encode([
                    'bots' => 3,
                    'messages_per_month' => 5000,
                    'knowledge_entries' => 200,
                    'products' => 2000,
                    'channels' => ['web_chatbot'],
                ]),
                'overage' => json_encode([
                    'cost_per_message' => 0.003,
                ]),
                'features' => json_encode([
                    '3 chatboți AI',
                    '5.000 mesaje/lună',
                    'Bază de cunoștințe (200 articole)',
                    'Integrare WooCommerce',
                    'Analytics avansat',
                    'Suport prioritar',
                ]),
                'description' => 'Pentru echipe care au nevoie de mai mulți chatboți și integrări.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'chat-business',
                'name' => 'Chat Business',
                'type' => 'webchat',
                'price_monthly' => 199.00,
                'price_yearly' => 159.00,
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 3,
                'limits' => json_encode([
                    'bots' => 10,
                    'messages_per_month' => 20000,
                    'knowledge_entries' => -1, // unlimited
                    'products' => 10000,
                    'channels' => ['web_chatbot'],
                ]),
                'overage' => json_encode([
                    'cost_per_message' => 0.002,
                ]),
                'features' => json_encode([
                    '10 chatboți AI',
                    '20.000 mesaje/lună',
                    'Bază de cunoștințe nelimitată',
                    'Integrare WooCommerce + API',
                    'A/B testing prompts',
                    'Manager dedicat',
                ]),
                'description' => 'Soluția completă pentru afaceri cu volum mare.',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // VOICE ADDON PLANS
            [
                'slug' => 'voice-starter',
                'name' => 'Voice Starter',
                'type' => 'voice',
                'price_monthly' => 49.00,
                'price_yearly' => 39.00,
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 10,
                'limits' => json_encode([
                    'minutes_per_month' => 100,
                ]),
                'overage' => json_encode([
                    'cost_per_minute' => 0.20,
                ]),
                'features' => json_encode([
                    '100 minute voce/lună',
                    'Voce nativă OpenAI',
                    'Transcrieri automate',
                ]),
                'description' => 'Addon vocal de bază pentru chatboți.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'voice-pro',
                'name' => 'Voice Pro',
                'type' => 'voice',
                'price_monthly' => 149.00,
                'price_yearly' => 119.00,
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 11,
                'limits' => json_encode([
                    'minutes_per_month' => 500,
                ]),
                'overage' => json_encode([
                    'cost_per_minute' => 0.15,
                ]),
                'features' => json_encode([
                    '500 minute voce/lună',
                    'Voce clonată ElevenLabs',
                    'Analiză sentiment',
                    'Sumar automat apeluri',
                ]),
                'description' => 'Voice addon avansat cu voce clonată și analytics.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'voice-enterprise',
                'name' => 'Voice Enterprise',
                'type' => 'voice',
                'price_monthly' => 0.00,
                'price_yearly' => 0.00,
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 12,
                'limits' => json_encode([
                    'minutes_per_month' => -1, // unlimited
                ]),
                'overage' => json_encode([
                    'cost_per_minute' => 0.00,
                ]),
                'features' => json_encode([
                    'Minute nelimitate',
                    'Voce clonată custom',
                    'SLA 99.99%',
                    'Integrare CRM',
                ]),
                'description' => 'Soluție enterprise cu preț personalizat.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
};
