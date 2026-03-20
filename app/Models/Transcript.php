<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transcript extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'call_id',
        'role',
        'content',
        'timestamp_ms',
    ];

    // Relationships

    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class);
    }

    // Scopes

    public function scopeByRole(Builder $query, string $role): Builder
    {
        return $query->where('role', $role);
    }
}
