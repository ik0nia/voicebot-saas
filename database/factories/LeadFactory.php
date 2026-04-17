<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Bot;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'bot_id' => Bot::factory(),
            'conversation_id' => Conversation::factory(),
            'session_id' => 's_' . fake()->uuid(),
            'status' => 'new',
            'pipeline_stage' => 'new',
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => '+407' . fake()->numerify('########'),
            'qualification_score' => 0,
            'capture_source' => 'chat',
            'capture_reason' => 'auto_extracted',
            'gdpr_consent' => false,
            'products_shown' => [],
            'custom_fields' => [],
        ];
    }

    public function prechat(): static
    {
        return $this->state(fn () => [
            'status' => 'qualified',
            'pipeline_stage' => 'contacted',
            'capture_source' => 'prechat',
            'capture_reason' => 'prechat_form',
            'gdpr_consent' => true,
        ]);
    }

    public function chatExtracted(): static
    {
        return $this->state(fn () => [
            'capture_source' => 'chat',
            'capture_reason' => 'auto_extracted',
            'qualification_score' => fake()->numberBetween(10, 80),
        ]);
    }

    public function won(): static
    {
        return $this->state(fn () => [
            'status' => 'converted',
            'pipeline_stage' => 'won',
            'won_at' => now(),
        ]);
    }
}
