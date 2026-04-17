<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceAvailabilityRule extends Model
{
    protected $fillable = [
        'bookable_resource_id', 'weekday',
        'start_time', 'end_time',
        'effective_from', 'effective_to',
    ];

    protected $casts = [
        'weekday' => 'integer',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function resource(): BelongsTo
    {
        return $this->belongsTo(BookableResource::class, 'bookable_resource_id');
    }
}
