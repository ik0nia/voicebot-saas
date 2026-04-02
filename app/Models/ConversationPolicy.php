<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\BelongsToTenant;

class ConversationPolicy extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'conversation_policies';

    protected $fillable = [
        'tenant_id',
        'bot_id',
        'tone',
        'verbosity',
        'emoji_allowed',
        'cta_aggressiveness',
        'lead_aggressiveness',
        'fallback_message',
        'escalation_message',
        'prohibited_phrases',
        'required_phrases',
        'brand_vocabulary',
        'custom_greeting',
        'custom_handoff_message',
        'custom_lead_prompt',
        'custom_out_of_stock',
        'business_rules',
        'snippets',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'prohibited_phrases' => 'array',
            'required_phrases' => 'array',
            'brand_vocabulary' => 'array',
            'business_rules' => 'array',
            'snippets' => 'array',
            'emoji_allowed' => 'boolean',
            'is_active' => 'boolean',
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
}
