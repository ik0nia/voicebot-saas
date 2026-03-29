<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    use HasFactory;

    protected $table = 'leads';

    protected $fillable = [
        'tenant_id',
        'bot_id',
        'conversation_id',
        'contact_id',
        'session_id',
        'status',
        'name',
        'email',
        'phone',
        'company',
        'project_type',
        'budget_range',
        'preferred_contact',
        'notes',
        'internal_notes',
        'qualification_score',
        'capture_source',
        'capture_reason',
        'gdpr_consent',
        'custom_fields',
        'products_shown',
        'sent_to_crm_at',
    ];

    protected function casts(): array
    {
        return [
            'custom_fields' => 'array',
            'products_shown' => 'array',
            'sent_to_crm_at' => 'datetime',
            'gdpr_consent' => 'boolean',
            'qualification_score' => 'integer',
        ];
    }

    // ─── Relationships ───

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    // ─── Scopes ───

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeQualified(Builder $query): Builder
    {
        return $query->where('status', 'qualified');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }
}
