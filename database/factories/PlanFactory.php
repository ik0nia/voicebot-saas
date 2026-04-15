<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        $slug = 'plan-' . $this->faker->unique()->word() . '-' . $this->faker->numberBetween(100, 9999);

        return [
            'slug' => $slug,
            'name' => 'Plan ' . ucfirst($this->faker->word()),
            'type' => 'webchat',
            'price_monthly' => 29.00,
            'price_yearly' => 290.00,
            'is_popular' => false,
            'is_active' => true,
            'is_public' => true,
            'sort_order' => 0,
            'limits' => ['messages_per_month' => 1000, 'bots' => 1],
            'overage' => ['cost_per_message' => 0.005],
            'features' => [],
            'topups' => [],
            'description' => null,
            'tenant_id' => null,
        ];
    }
}
