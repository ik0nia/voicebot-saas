<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallEvent extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'call_id',
        'type',
        'metadata',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    // Relationships

    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class);
    }
}
