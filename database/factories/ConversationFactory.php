<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Bot;
use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'bot_id' => Bot::factory(),
            'channel_id' => Channel::factory(),
            'external_conversation_id' => null,
            'contact_identifier' => null,
            'contact_name' => null,
            'visitor_id' => 'v_' . Str::random(16),
            'status' => 'active',
            'messages_count' => 0,
            'cost_cents' => 0,
            'metadata' => [],
            'started_at' => now(),
            'last_activity_at' => now(),
            'lead_score' => 0,
            'opportunity_score' => 0,
            'is_opportunity' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => 'active',
            'ended_at' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'ended_at' => now(),
        ]);
    }

    public function withPageContext(string $pageType, array $extra = []): static
    {
        return $this->state(fn () => [
            'metadata' => array_merge([
                'page_context' => array_merge(['type' => $pageType], $extra),
            ]),
        ]);
    }
}
