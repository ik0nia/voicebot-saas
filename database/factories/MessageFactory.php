<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'direction' => 'inbound',
            'content' => fake()->sentence(),
            'content_type' => 'text',
            'external_message_id' => null,
            'metadata' => [],
            'ai_model' => null,
            'ai_provider' => null,
            'input_tokens' => 0,
            'output_tokens' => 0,
            'cost_cents' => 0,
            'sent_at' => now(),
        ];
    }

    public function fromUser(string $content = null): static
    {
        return $this->state(fn () => [
            'direction' => 'inbound',
            'content' => $content ?? fake()->sentence(),
            'ai_model' => null,
            'ai_provider' => null,
        ]);
    }

    public function fromBot(string $content = null, string $model = 'claude-sonnet-4-6'): static
    {
        return $this->state(fn () => [
            'direction' => 'outbound',
            'content' => $content ?? fake()->sentence(),
            'ai_model' => $model,
            'ai_provider' => 'anthropic',
            'input_tokens' => fake()->numberBetween(100, 1500),
            'output_tokens' => fake()->numberBetween(50, 400),
            'cost_cents' => fake()->randomFloat(4, 0.01, 2.5),
        ]);
    }
}
