<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebsiteScan extends Model
{
    protected $fillable = [
        'bot_id',
        'base_url',
        'status',
        'max_pages',
        'pages_found',
        'pages_processed',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'max_pages' => 'integer',
            'pages_found' => 'integer',
            'pages_processed' => 'integer',
        ];
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(WebsiteScanPage::class, 'scan_id');
    }

    public function isRunning(): bool
    {
        return in_array($this->status, ['pending', 'scanning']);
    }

    public function progressPercent(): int
    {
        if ($this->max_pages === 0) return 0;
        return min(100, (int) round(($this->pages_processed / $this->max_pages) * 100));
    }
}
