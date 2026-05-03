<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeSearchLog extends Model
{
    use BelongsToTenant;

    public $timestamps = false;
    protected $table = 'knowledge_search_log';

    protected $fillable = [
        'tenant_id', 'bot_id', 'query',
        'results_count', 'top_score',
        'used_reranking', 'used_fallback',
        'chunk_ids', 'created_at',
    ];

    protected $casts = [
        'top_score' => 'float',
        'used_reranking' => 'boolean',
        'used_fallback' => 'boolean',
        'chunk_ids' => 'array',
        'created_at' => 'datetime',
    ];

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }
}
