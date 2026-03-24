<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeConnector extends Model
{
    protected $fillable = [
        'bot_id',
        'type',
        'site_url',
        'credentials',
        'sync_settings',
        'status',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'sync_settings' => 'array',
            'last_synced_at' => 'datetime',
        ];
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function isConnected(): bool
    {
        return $this->status === 'connected';
    }
}
