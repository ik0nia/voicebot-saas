<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AbExperiment extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'bot_id',
        'name',
        'type',
        'status',
        'variants',
        'metric',
        'min_conversations',
        'confidence_level',
        'started_at',
        'ended_at',
        'results',
    ];

    protected function casts(): array
    {
        return [
            'variants' => 'array',
            'results' => 'array',
            'confidence_level' => 'float',
            'min_conversations' => 'integer',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    // ── Relationships ──

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AbAssignment::class, 'experiment_id');
    }

    // ── Scopes ──

    public function scopeRunning(Builder $query): Builder
    {
        return $query->where('status', 'running');
    }

    public function scopeForBot(Builder $query, int $botId): Builder
    {
        return $query->where('bot_id', $botId);
    }

    // ── Methods ──

    /**
     * Assign a variant to a conversation using weighted random selection.
     * Returns the assigned variant array.
     */
    public function assignVariant(int $conversationId): array
    {
        // Check if already assigned
        $existing = $this->assignments()
            ->where('conversation_id', $conversationId)
            ->first();

        if ($existing) {
            return $this->getVariantById($existing->variant_id);
        }

        // Weighted random selection
        $variant = $this->weightedRandomVariant();

        AbAssignment::create([
            'experiment_id' => $this->id,
            'conversation_id' => $conversationId,
            'variant_id' => $variant['id'],
        ]);

        return $variant;
    }

    /**
     * Get variant config array by variant ID.
     */
    public function getVariantById(string $variantId): array
    {
        $variants = $this->variants ?? [];

        foreach ($variants as $variant) {
            if (($variant['id'] ?? '') === $variantId) {
                return $variant;
            }
        }

        return $variants[0] ?? [];
    }

    /**
     * Check if experiment has reached minimum conversations and can be evaluated.
     */
    public function isComplete(): bool
    {
        if ($this->status === 'completed') {
            return true;
        }

        $totalAssignments = $this->assignments()->count();

        return $totalAssignments >= $this->min_conversations;
    }

    /**
     * Mark experiment as completed with results.
     */
    public function declareWinner(array $results): void
    {
        $this->update([
            'status' => 'completed',
            'ended_at' => now(),
            'results' => $results,
        ]);
    }

    /**
     * Weighted random selection from variants.
     */
    private function weightedRandomVariant(): array
    {
        $variants = $this->variants ?? [];

        if (empty($variants)) {
            return [];
        }

        if (count($variants) === 1) {
            return $variants[0];
        }

        $totalWeight = array_sum(array_column($variants, 'weight'));
        if ($totalWeight <= 0) {
            return $variants[array_rand($variants)];
        }

        $random = random_int(1, $totalWeight);
        $cumulative = 0;

        foreach ($variants as $variant) {
            $cumulative += ($variant['weight'] ?? 0);
            if ($random <= $cumulative) {
                return $variant;
            }
        }

        return $variants[0];
    }
}
