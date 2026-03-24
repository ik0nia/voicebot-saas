<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeAgentRun extends Model
{
    protected $fillable = [
        'bot_id',
        'agent_slug',
        'user_input',
        'custom_prompt',
        'generated_content',
        'status',
        'knowledge_id',
        'tokens_used',
        'model_used',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'tokens_used' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function knowledge(): BelongsTo
    {
        return $this->belongsTo(BotKnowledge::class, 'knowledge_id');
    }

    public function agent()
    {
        return KnowledgeAgent::where('slug', $this->agent_slug)->first();
    }
}
