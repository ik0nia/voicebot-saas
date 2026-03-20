<?php

namespace Database\Factories;

use App\Models\Bot;
use App\Models\Call;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Call>
 */
class CallFactory extends Factory
{
    protected $model = Call::class;

    public function definition(): array
    {
        $duration = fake()->numberBetween(10, 600);
        $startedAt = now()->subMinutes(fake()->numberBetween(1, 1440));

        return [
            'tenant_id' => Tenant::factory(),
            'bot_id' => Bot::factory(),
            'caller_number' => '+40' . fake()->numerify('#########'),
            'direction' => fake()->randomElement(['inbound', 'outbound']),
            'status' => fake()->randomElement(['completed', 'failed', 'in_progress']),
            'duration_seconds' => $duration,
            'cost_cents' => (int) ceil($duration / 60 * 2),
            'started_at' => $startedAt,
            'ended_at' => $startedAt->copy()->addSeconds($duration),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => 'completed']);
    }

    public function failed(): static
    {
        return $this->state(fn () => ['status' => 'failed']);
    }

    public function inProgress(): static
    {
        return $this->state(fn () => [
            'status' => 'in_progress',
            'ended_at' => null,
        ]);
    }

    public function inbound(): static
    {
        return $this->state(fn () => ['direction' => 'inbound']);
    }

    public function outbound(): static
    {
        return $this->state(fn () => ['direction' => 'outbound']);
    }
}
