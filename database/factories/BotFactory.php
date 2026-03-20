<?php

namespace Database\Factories;

use App\Models\Bot;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bot>
 */
class BotFactory extends Factory
{
    protected $model = Bot::class;

    public function definition(): array
    {
        $name = fake()->words(2, true) . ' Bot';
        return [
            'tenant_id' => Tenant::factory(),
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(6),
            'system_prompt' => 'Ești un asistent vocal pentru ' . fake()->company(),
            'voice' => fake()->randomElement(['alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer']),
            'language' => 'ro',
            'settings' => ['vad_threshold' => 0.5, 'temperature' => 0.7],
            'is_active' => true,
            'calls_count' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
