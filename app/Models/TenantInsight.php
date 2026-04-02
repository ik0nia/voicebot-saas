<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\BelongsToTenant;

class TenantInsight extends Model
{
    use BelongsToTenant;
    protected $fillable = [
        'tenant_id', 'bot_id', 'insight_type', 'severity',
        'title', 'description', 'data', 'action_items',
        'is_dismissed', 'valid_from', 'valid_until',
    ];

    protected $casts = [
        'data' => 'array',
        'action_items' => 'array',
        'is_dismissed' => 'boolean',
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function bot(): BelongsTo { return $this->belongsTo(Bot::class); }

    public function scopeActive($q)
    {
        return $q->where('valid_from', '<=', now())
            ->where(fn($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', now()));
    }
    public function scopeUndismissed($q) { return $q->where('is_dismissed', false); }
    public function scopeSeverity($q, string $level) { return $q->where('severity', $level); }
}
