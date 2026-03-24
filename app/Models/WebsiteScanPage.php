<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebsiteScanPage extends Model
{
    protected $fillable = [
        'scan_id',
        'url',
        'title',
        'content',
        'status',
        'content_hash',
    ];

    public function scan(): BelongsTo
    {
        return $this->belongsTo(WebsiteScan::class, 'scan_id');
    }
}
