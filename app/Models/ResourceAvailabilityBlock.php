<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceAvailabilityBlock extends Model
{
    protected $fillable = [
        'bookable_resource_id', 'starts_at', 'ends_at',
        'reason', 'source', 'external_event_id',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function resource(): BelongsTo
    {
        return $this->belongsTo(BookableResource::class, 'bookable_resource_id');
    }
}
