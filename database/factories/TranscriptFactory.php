<?php

namespace Database\Factories;

use App\Models\Call;
use App\Models\Transcript;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transcript>
 */
class TranscriptFactory extends Factory
{
    protected $model = Transcript::class;

    public function definition(): array
    {
        return [
            'call_id' => Call::factory(),
            'role' => fake()->randomElement(['user', 'assistant']),
            'content' => fake()->sentence(10),
            'timestamp_ms' => fake()->numberBetween(1000, 300000),
        ];
    }

    public function user(): static
    {
        return $this->state(fn () => ['role' => 'user']);
    }

    public function assistant(): static
    {
        return $this->state(fn () => ['role' => 'assistant']);
    }
}
