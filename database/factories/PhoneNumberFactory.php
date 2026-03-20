<?php

namespace Database\Factories;

use App\Models\PhoneNumber;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PhoneNumber>
 */
class PhoneNumberFactory extends Factory
{
    protected $model = PhoneNumber::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'bot_id' => null,
            'number' => '+40' . fake()->unique()->numerify('#########'),
            'provider' => 'twilio',
            'friendly_name' => fake()->words(2, true),
            'monthly_cost_cents' => 100,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
