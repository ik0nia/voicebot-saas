<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = fake()->company();
        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(4),
            'plan' => fake()->randomElement(['starter', 'professional', 'enterprise']),
            'settings' => ['industry' => 'technology'],
            'trial_ends_at' => now()->addDays(14),
        ];
    }

    public function starter(): static
    {
        return $this->state(fn () => ['plan' => 'starter']);
    }

    public function professional(): static
    {
        return $this->state(fn () => ['plan' => 'professional']);
    }

    public function enterprise(): static
    {
        return $this->state(fn () => ['plan' => 'enterprise']);
    }
}
