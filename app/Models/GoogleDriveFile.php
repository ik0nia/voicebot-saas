<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleDriveFile extends Model
{
    protected $fillable = [
        'connector_id',
        'knowledge_id',
        'drive_file_id',
        'name',
        'mime_type',
        'icon_url',
        'web_view_link',
        'category',
        'user_description',
        'status',
        'error_message',
        'drive_modified_at',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'drive_modified_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function connector(): BelongsTo
    {
        return $this->belongsTo(KnowledgeConnector::class, 'connector_id');
    }

    public function knowledge(): BelongsTo
    {
        return $this->belongsTo(BotKnowledge::class, 'knowledge_id');
    }

    /**
     * Resolve the human-readable label + retrieval prompt for this file's category.
     */
    public function categoryConfig(): array
    {
        $all = config('google-drive-categories', []);
        return $all[$this->category] ?? $all['other'];
    }
}
