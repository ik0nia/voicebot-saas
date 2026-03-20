<?php

namespace Database\Factories;

use App\Models\Bot;
use App\Models\BotKnowledge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BotKnowledge>
 */
class BotKnowledgeFactory extends Factory
{
    protected $model = BotKnowledge::class;

    public function definition(): array
    {
        return [
            'bot_id' => Bot::factory(),
            'type' => fake()->randomElement(['text', 'url', 'pdf']),
            'title' => fake()->sentence(3),
            'content' => fake()->paragraphs(2, true),
            'status' => 'ready',
            'chunk_index' => 0,
        ];
    }

    public function text(): static
    {
        return $this->state(fn () => ['type' => 'text']);
    }

    public function url(): static
    {
        return $this->state(fn () => ['type' => 'url']);
    }

    public function pdf(): static
    {
        return $this->state(fn () => ['type' => 'pdf']);
    }
}
