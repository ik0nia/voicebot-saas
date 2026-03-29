<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    use HasFactory;

    protected $table = 'leads';

    /**
     * Pipeline stages in order.
     */
    public const STAGES = [
        'new'       => 'Nou',
        'contacted' => 'Contactat',
        'scheduled' => 'Programat',
        'met'       => 'Întâlnire',
        'quoted'    => 'Ofertă trimisă',
        'won'       => 'Câștigat',
        'lost'      => 'Pierdut',
    ];

    public const STAGE_COLORS = [
        'new'       => 'bg-blue-100 text-blue-700',
        'contacted' => 'bg-sky-100 text-sky-700',
        'scheduled' => 'bg-amber-100 text-amber-700',
        'met'       => 'bg-purple-100 text-purple-700',
        'quoted'    => 'bg-indigo-100 text-indigo-700',
        'won'       => 'bg-emerald-100 text-emerald-700',
        'lost'      => 'bg-slate-100 text-slate-500',
    ];

    protected $fillable = [
        'tenant_id', 'bot_id', 'conversation_id', 'contact_id', 'session_id',
        'status', 'pipeline_stage',
        'name', 'email', 'phone', 'company',
        'project_type', 'service_type', 'budget_range',
        'preferred_date', 'preferred_time_slot', 'preferred_contact',
        'notes', 'internal_notes',
        'qualification_score', 'capture_source', 'capture_reason', 'source_page_url',
        'assigned_to', 'outcome', 'estimated_value',
        'gdpr_consent', 'custom_fields', 'products_shown',
        'sent_to_crm_at', 'contacted_at', 'scheduled_at', 'met_at', 'quoted_at', 'won_at', 'lost_at', 'lost_reason',
    ];

    protected function casts(): array
    {
        return [
            'custom_fields' => 'array',
            'products_shown' => 'array',
            'gdpr_consent' => 'boolean',
            'qualification_score' => 'integer',
            'estimated_value' => 'decimal:2',
            'preferred_date' => 'date',
            'sent_to_crm_at' => 'datetime',
            'contacted_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'met_at' => 'datetime',
            'quoted_at' => 'datetime',
            'won_at' => 'datetime',
            'lost_at' => 'datetime',
        ];
    }

    // ─── Relationships ───

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function bot(): BelongsTo { return $this->belongsTo(Bot::class); }
    public function conversation(): BelongsTo { return $this->belongsTo(Conversation::class); }
    public function contact(): BelongsTo { return $this->belongsTo(Contact::class); }
    public function callbacks(): HasMany { return $this->hasMany(CallbackRequest::class); }

    // ─── Pipeline methods ───

    /**
     * Advance lead to next pipeline stage with automatic timestamp.
     */
    public function advanceTo(string $stage, array $extra = []): self
    {
        $timestampField = match($stage) {
            'contacted' => 'contacted_at',
            'scheduled' => 'scheduled_at',
            'met' => 'met_at',
            'quoted' => 'quoted_at',
            'won' => 'won_at',
            'lost' => 'lost_at',
            default => null,
        };

        $data = array_merge(['pipeline_stage' => $stage], $extra);
        if ($timestampField && !$this->$timestampField) {
            $data[$timestampField] = now();
        }

        // Also update legacy status for backward compatibility
        $data['status'] = match($stage) {
            'new' => 'new',
            'contacted', 'scheduled', 'met' => 'qualified',
            'quoted' => 'qualified',
            'won' => 'converted',
            'lost' => 'dismissed',
            default => $this->status,
        };

        $this->update($data);
        return $this;
    }

    /**
     * Convert to scheduled (callback) with scheduling data.
     */
    public function scheduleCallback(?string $serviceType = null, ?string $preferredDate = null, ?string $timeSlot = null): self
    {
        return $this->advanceTo('scheduled', array_filter([
            'service_type' => $serviceType,
            'preferred_date' => $preferredDate,
            'preferred_time_slot' => $timeSlot,
        ]));
    }

    /**
     * Check if lead has a scheduled callback.
     */
    public function hasCallback(): bool
    {
        return $this->pipeline_stage === 'scheduled' || $this->preferred_date !== null;
    }

    public function getStageLabelAttribute(): string
    {
        return self::STAGES[$this->pipeline_stage] ?? $this->pipeline_stage;
    }

    public function getStageColorAttribute(): string
    {
        return self::STAGE_COLORS[$this->pipeline_stage] ?? 'bg-slate-100 text-slate-600';
    }

    public function getTimeSlotLabelAttribute(): string
    {
        return match($this->preferred_time_slot) {
            'dimineata' => 'Dimineața (08-12)',
            'dupa-amiaza' => 'După-amiaza (12-17)',
            'seara' => 'Seara (17-20)',
            default => $this->preferred_time_slot ?? '—',
        };
    }

    // ─── Scopes ───

    public function scopeStage(Builder $q, string $stage): Builder { return $q->where('pipeline_stage', $stage); }
    public function scopeActive(Builder $q): Builder { return $q->whereNotIn('pipeline_stage', ['won', 'lost']); }
    public function scopeScheduled(Builder $q): Builder { return $q->where('pipeline_stage', 'scheduled'); }
    public function scopeWon(Builder $q): Builder { return $q->where('pipeline_stage', 'won'); }
    public function scopeWithCallback(Builder $q): Builder { return $q->whereNotNull('preferred_date'); }
}
