<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'direction',
        'content',
        'content_type',
        'external_message_id',
        'metadata',
        'ai_model',
        'ai_provider',
        'input_tokens',
        'output_tokens',
        'cost_cents',
        'sent_at',
        'delivered_at',
        'read_at',
        'detected_intents',
        'pipelines_executed',
        'knowledge_chunks_used',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'detected_intents' => 'array',
            'pipelines_executed' => 'array',
            'knowledge_chunks_used' => 'array',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    // Relationships

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
