<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Bot;
use App\Models\Channel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Channel>
 */
class ChannelFactory extends Factory
{
    protected $model = Channel::class;

    public function definition(): array
    {
        return [
            'bot_id' => Bot::factory(),
            'type' => Channel::TYPE_WEB_CHATBOT,
            'name' => fake()->words(2, true) . ' widget',
            'external_id' => null,
            'config' => [],
            'webhook_secret' => Str::random(32),
            'is_active' => true,
            'status' => 'active',
            'last_activity_at' => null,
        ];
    }

    public function configure(): static
    {
        // Channel.booted() derives tenant_id from bot, but the factory may be
        // used with `make()` (no save) where booted() doesn't run. Mirror the
        // derivation here so $channel->tenant_id is always populated.
        return $this->afterMaking(function (Channel $channel): void {
            if (!$channel->tenant_id && $channel->bot_id) {
                $bot = Bot::find($channel->bot_id);
                if ($bot) {
                    $channel->tenant_id = $bot->tenant_id;
                }
            }
        });
    }

    public function widget(): static
    {
        return $this->state(fn () => [
            'type' => Channel::TYPE_WEB_CHATBOT,
            'name' => 'Widget web',
        ]);
    }

    public function voice(): static
    {
        return $this->state(fn () => [
            'type' => Channel::TYPE_VOICE,
            'name' => 'Voice',
        ]);
    }

    public function whatsapp(): static
    {
        return $this->state(fn () => [
            'type' => Channel::TYPE_WHATSAPP,
            'name' => 'WhatsApp',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false, 'status' => 'paused']);
    }
}
