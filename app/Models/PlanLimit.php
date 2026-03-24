<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanLimit extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'price_monthly',
        'price_annual',
        'limits',
        'features',
        'allowed_agents',
        'allowed_file_formats',
        'max_upload_size_kb',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'limits' => 'array',
            'features' => 'array',
            'allowed_agents' => 'array',
            'allowed_file_formats' => 'array',
            'max_upload_size_kb' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    //  Helpers
    // ═══════════════════════════════════════════════════════════════

    /**
     * Verifică dacă un agent este permis pe acest plan.
     * Dacă allowed_agents este null sau gol, toți agenții sunt permiși.
     */
    public function isAgentAllowed(string $slug): bool
    {
        $agents = $this->allowed_agents;

        // null sau array gol => toți agenții disponibili
        if ($agents === null || $agents === []) {
            return true;
        }

        return in_array($slug, $agents, true);
    }

    /**
     * Verifică dacă un format de fișier este permis pe acest plan.
     */
    public function isFileFormatAllowed(string $format): bool
    {
        $formats = $this->allowed_file_formats ?? [];

        return in_array(strtolower($format), array_map('strtolower', $formats), true);
    }

    /**
     * Verifică dacă o funcționalitate (feature flag) este activă pe acest plan.
     */
    public function hasFeature(string $feature): bool
    {
        $features = $this->features ?? [];

        return !empty($features[$feature]);
    }

    /**
     * Returnează valoarea unei limite din JSON-ul limits.
     * Returnează null dacă limita nu este definită.
     */
    public function getLimit(string $key, mixed $default = null): mixed
    {
        $limits = $this->limits ?? [];

        return $limits[$key] ?? $default;
    }

    /**
     * Returnează numărul maxim de site-uri permise pe acest plan.
     */
    public function getMaxSites(): int
    {
        return (int) $this->getLimit('max_sites', 1);
    }

    /**
     * Returnează dimensiunea maximă de upload în bytes.
     */
    public function getMaxUploadSizeBytes(): int
    {
        return ($this->max_upload_size_kb ?? 0) * 1024;
    }

    // ═══════════════════════════════════════════════════════════════
    //  Scopes
    // ═══════════════════════════════════════════════════════════════

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ═══════════════════════════════════════════════════════════════
    //  Static helpers
    // ═══════════════════════════════════════════════════════════════

    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }

    public static function getFreePlan(): self
    {
        return static::findBySlug('free') ?? new self([
            'slug' => 'free',
            'name' => 'Free',
            'price_monthly' => 0,
            'limits' => [
                'max_bots' => 1,
                'max_sites' => 1,
                'max_knowledge_kb' => 50,
                'max_agents' => 5,
                'max_agent_runs_per_month' => 10,
                'max_tokens_per_month' => 100_000,
                'max_scan_pages_per_month' => 20,
                'max_connectors' => 0,
            ],
            'features' => [],
            'allowed_agents' => [
                'product-specialist',
                'faq-generator',
                'policy-writer',
                'response-templates',
                'greeting-closing',
            ],
            'allowed_file_formats' => ['text', 'txt', 'url'],
            'max_upload_size_kb' => 2_048,
        ]);
    }
}
