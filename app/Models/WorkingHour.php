<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkingHour extends Model
{
    protected $table = 'working_hours';

    protected $fillable = [
        'staff_member_id', 'weekday', 'start_time', 'end_time',
    ];

    protected $casts = [
        'weekday' => 'integer',
        // Stored as TIME — Laravel serialises to H:i:s string,
        // which keeps availability math simple (no date coupling).
    ];

    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class);
    }
}
