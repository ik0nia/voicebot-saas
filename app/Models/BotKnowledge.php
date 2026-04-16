<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class BotKnowledge extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'bot_knowledge';

    protected $fillable = [
        'tenant_id',
        'bot_id',
        'type',
        'source_type',
        'source_id',
        'title',
        'content',
        'content_language',
        'status',
        'error_message',
        'embedding_model',
        'chunk_index',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        // Derive tenant_id from the parent bot when not set. Runs before the
        // BelongsToTenant trait's own auth()-based stamping, so queue jobs and
        // anonymous contexts (no auth user) still produce correctly scoped rows.
        static::creating(function (self $model): void {
            if (empty($model->tenant_id) && $model->bot_id) {
                $model->tenant_id = DB::table('bots')
                    ->where('id', $model->bot_id)
                    ->value('tenant_id');
            }
        });
    }

    // Relationships

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    // Scopes

    public function scopeReady(Builder $query): Builder
    {
        return $query->where('status', 'ready');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }
}
